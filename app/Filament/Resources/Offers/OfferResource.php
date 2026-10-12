<?php

namespace App\Filament\Resources\Offers;

use App\Enums\MessageChannel;
use App\Enums\OfferStatus;
use App\Enums\Role;
use App\Filament\Resources\Applications\ApplicationResource;
use App\Filament\Resources\Offers\Pages\EditOffer;
use App\Filament\Resources\Offers\Pages\ListOffers;
use App\Models\Activity;
use App\Models\Offer;
use App\Services\Messenger;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use UnitEnum;

/** Offer letters: draft → approval → e-sign → signed. */
class OfferResource extends Resource
{
    protected static ?string $model = Offer::class;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedDocumentCheck;

    protected static string | UnitEnum | null $navigationGroup = 'Recruit';

    protected static ?int $navigationSort = 7;

    public static function getNavigationBadge(): ?string
    {
        $pending = Offer::whereIn('status', [OfferStatus::PendingApproval->value, OfferStatus::Sent->value])->count();

        return $pending ? (string) $pending : null;
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Awaiting approval or signature';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(['default' => 1, 'lg' => 3])
            ->components([
                Section::make('Offer letter')
                    ->columnSpan(['lg' => 2])
                    ->schema([
                        Textarea::make('body')->hiddenLabel()->rows(18)->required()
                            ->disabled(fn (?Offer $record) => in_array($record?->status, [OfferStatus::Sent, OfferStatus::Signed], true)),
                    ]),
                Section::make('Terms')
                    ->columnSpan(['lg' => 1])
                    ->schema([
                        TextEntry::make('candidate')
                            ->state(fn (?Offer $record) => $record ? new HtmlString('<b>'.e($record->application->candidate->name).'</b><br><span style="opacity:.7">'.e($record->application->jobOpening->title).'</span>') : '—')
                            ->html(),
                        TextInput::make('salary')->numeric()->required()->prefix('$'),
                        Select::make('salary_period')->options(['year' => 'Per year', 'hour' => 'Per hour'])->required()->native(false),
                        DatePicker::make('start_date')->required()->native(false),
                        DatePicker::make('expires_at')->label('Offer expires')->native(false),
                        TextEntry::make('status_label')->label('Status')
                            ->state(fn (?Offer $record) => $record?->status->getLabel())
                            ->badge(),
                        TextEntry::make('signature_preview')
                            ->label('Signed by')
                            ->html()
                            ->visible(fn (?Offer $record) => $record?->status === OfferStatus::Signed)
                            ->state(fn (?Offer $record) => $record ? new HtmlString(
                                ($record->signature ? '<img src="'.e($record->signature).'" alt="Signature" style="max-height:70px;background:#fff;border-radius:8px;padding:4px">' : '')
                                .'<div>'.e($record->signed_name).' · '.$record->signed_at?->format('M j, Y g:i A').'</div>'
                            ) : null),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['application.candidate', 'application.jobOpening']))
            ->columns([
                TextColumn::make('application.candidate.first_name')->label('Candidate')
                    ->formatStateUsing(fn (Offer $r) => $r->application->candidate->name)
                    ->description(fn (Offer $r) => $r->application->jobOpening->title)
                    ->weight('semibold')
                    ->searchable(['first_name', 'last_name']),
                TextColumn::make('salary')->formatStateUsing(fn (Offer $r) => $r->salaryLabel()),
                TextColumn::make('start_date')->date('M j, Y')->sortable(),
                TextColumn::make('status')->badge(),
                TextColumn::make('signed_at')->label('Signed')->since()->placeholder('—'),
                TextColumn::make('updated_at')->label('Updated')->since()->color('gray'),
            ])
            ->defaultSort('updated_at', 'desc')
            ->filters([SelectFilter::make('status')->options(OfferStatus::class)])
            ->recordActions([
                EditAction::make()->label('Open'),
                Action::make('candidate')->icon(Heroicon::OutlinedUser)->url(fn (Offer $r) => ApplicationResource::getUrl('view', ['record' => $r->application_id])),
            ]);
    }

    /** Workflow actions shared by the edit page. */
    public static function workflowActions(\Closure $offer): array
    {
        return [
            Action::make('requestApproval')
                ->label('Request approval')
                ->icon(Heroicon::OutlinedPaperAirplane)
                ->visible(fn () => $offer()->status === OfferStatus::Draft)
                ->action(function () use ($offer) {
                    $offer()->update(['status' => OfferStatus::PendingApproval]);
                    Activity::log('Offer sent for approval', $offer()->application, 'offer');
                    Notification::make()->title('Approval requested')->success()->send();
                }),
            Action::make('approve')
                ->icon(Heroicon::OutlinedCheckBadge)
                ->color('success')
                ->visible(fn () => in_array($offer()->status, [OfferStatus::Draft, OfferStatus::PendingApproval], true) && auth()->user()?->role !== Role::Recruiter)
                ->requiresConfirmation()
                ->action(function () use ($offer) {
                    $offer()->update(['status' => OfferStatus::Approved, 'approved_by' => auth()->id()]);
                    Activity::log('Offer approved by '.auth()->user()->name, $offer()->application, 'offer');
                    Notification::make()->title('Offer approved')->success()->send();
                }),
            Action::make('send')
                ->label('Send for e-signature')
                ->icon(Heroicon::OutlinedPencilSquare)
                ->color('primary')
                ->visible(fn () => $offer()->status === OfferStatus::Approved)
                ->requiresConfirmation()
                ->modalDescription('The candidate receives an email with a secure link to review and sign.')
                ->action(function () use ($offer) {
                    $o = $offer();
                    $o->update(['status' => OfferStatus::Sent, 'sent_at' => now()]);
                    app(Messenger::class)->send(
                        $o->application->candidate, MessageChannel::Email,
                        "Hi {$o->application->candidate->first_name},\n\nWe're excited to offer you the {$o->application->jobOpening->title} position! Review and e-sign your offer letter here:\n\n{$o->signingUrl()}\n\n".config('hiredesk.company'),
                        'Your offer from '.config('hiredesk.company'),
                        $o->application,
                    );
                    Notification::make()->title('Offer sent for signature')->success()->send();
                }),
            Action::make('link')
                ->label('Signing page')
                ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                ->color('gray')
                ->visible(fn () => in_array($offer()->status, [OfferStatus::Sent, OfferStatus::Signed], true))
                ->url(fn () => $offer()->signingUrl(), shouldOpenInNewTab: true),
            Action::make('pdf')
                ->label('Print / PDF')
                ->icon(Heroicon::OutlinedPrinter)
                ->color('gray')
                ->url(fn () => route('offers.show', ['token' => $offer()->token, 'print' => 1]), shouldOpenInNewTab: true),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOffers::route('/'),
            'edit' => EditOffer::route('/{record}/edit'),
        ];
    }
}
