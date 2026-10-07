<?php

namespace App\Filament\Resources\Issues;

use App\Enums\PublicationStatus;
use App\Filament\Resources\Issues\Pages\CreateIssue;
use App\Filament\Resources\Issues\Pages\EditIssue;
use App\Filament\Resources\Issues\Pages\ListIssues;
use App\Jobs\ProcessPdfPagesJob;
use App\Models\Issue;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use UnitEnum;

class IssueResource extends Resource
{
    protected static ?string $model = Issue::class;

    protected static string|UnitEnum|null $navigationGroup = 'التحرير والنشر';

    protected static ?string $modelLabel = 'عدد دوري';

    protected static ?string $pluralModelLabel = 'الأعداد الدورية';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    public static function getNavigationBadge(): ?string
    {
        return (string) Issue::published()->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('بيانات العدد والتوثيق')
                    ->description('المعلومات الأساسية وتاريخ الإصدار الرسمي للعدد')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('issue_number')
                                ->label('رقم العدد')
                                ->numeric()
                                ->required()
                                ->unique(ignoreRecord: true)
                                ->helperText('الرقم التسلسلي الرسمي للعدد الدوري.'),

                            DatePicker::make('published_at')
                                ->label('تاريخ النشر')
                                ->native(false)
                                ->displayFormat('Y-m-d')
                                ->default(now()),
                        ]),

                        TextInput::make('title')
                            ->label('عنوان ملف العدد')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (string $operation, $state, callable $set) {
                                if ($operation === 'create') {
                                    $set('slug', Str::slug($state));
                                }
                            }),

                        TextInput::make('slug')
                            ->label('الرابط الثابت (Slug)')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),

                        TextInput::make('pages_count')
                            ->label('عدد الصفحات')
                            ->numeric()
                            ->default(0)
                            ->helperText('يتم تحديثه تلقائياً عند تفريغ صفحات PDF.'),
                    ]),

                Section::make('الغلاف والملف الرقمي')
                    ->description('ملفات المجلة عالية الدقة للطباعة والمستعرض الرقمي')
                    ->schema([
                        FileUpload::make('cover_image')
                            ->label('صورة غلاف العدد')
                            ->image()
                            ->disk('public')
                            ->directory('covers')
                            ->visibility('public')
                            ->imageEditor()
                            ->helperText('الصورة الرئيسية التي تظهر في واجهة المجلة والمستعرض.'),

                        FileUpload::make('pdf_file_path')
                            ->label('ملف العدد الكامل (PDF)')
                            ->acceptedFileTypes(['application/pdf'])
                            ->maxSize(102400) // 100MB
                            ->disk('public')
                            ->directory('issues')
                            ->visibility('public')
                            ->helperText('ملف PDF عالي الجودة لمعالجته واستخراج الصفحات بدقة WebP.'),
                    ]),

                Section::make('حالة النشر والاعتماد')
                    ->description('تحديد مستوى ظهور العدد للجمهور')
                    ->schema([
                        Select::make('status')
                            ->label('حالة النشر')
                            ->options(PublicationStatus::class)
                            ->default(PublicationStatus::Draft)
                            ->required()
                            ->disabled(fn () => ! auth()->user()?->can('publish', Issue::class))
                            ->helperText(fn () => ! auth()->user()?->can('publish', Issue::class)
                                ? 'اعتماد ونشر العدد يتطلب صلاحية رئيس التحرير أو المشرف العام.'
                                : 'حدد حالة النشر للعدد.'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('cover_image')
                    ->label('الغلاف')
                    ->disk('public')
                    ->square()
                    ->defaultImageUrl('/images/placeholder-cover.jpg'),

                TextColumn::make('issue_number')
                    ->label('العدد')
                    ->sortable()
                    ->searchable()
                    ->badge()
                    ->color('amber'),

                TextColumn::make('title')
                    ->label('عنوان العدد')
                    ->searchable()
                    ->wrap()
                    ->weight('bold'),

                TextColumn::make('published_at')
                    ->label('تاريخ النشر')
                    ->date('Y-m-d')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->color(fn (PublicationStatus $state): string => match ($state) {
                        PublicationStatus::Published => 'success',
                        PublicationStatus::Review => 'warning',
                        PublicationStatus::Draft => 'gray',
                        PublicationStatus::Archived => 'danger',
                    }),

                TextColumn::make('pages_count')
                    ->label('الصفحات')
                    ->numeric()
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('articles_count')
                    ->counts('articles')
                    ->label('المقالات')
                    ->badge()
                    ->color('info')
                    ->alignCenter(),
            ])
            ->defaultSort('issue_number', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('حالة النشر')
                    ->options(PublicationStatus::class),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('process_pdf')
                    ->label('معالجة صفحات PDF')
                    ->icon(Heroicon::OutlinedCog6Tooth)
                    ->color('amber')
                    ->requiresConfirmation()
                    ->modalHeading('معالجة وتفريغ صفحات المجلة')
                    ->modalDescription('سيتم تشغيل مهمة في الخلفية لتفريغ صفحات ملف PDF إلى صور WebP تفاعلية للمستعرض.')
                    ->action(function (Issue $record) {
                        if (! $record->pdf_file_path) {
                            Notification::make()
                                ->title('تنبيه')
                                ->body('يرجى رفع ملف PDF لهذا العدد أولاً قبل بدء المعالجة.')
                                ->warning()
                                ->send();

                            return;
                        }

                        ProcessPdfPagesJob::dispatch($record);

                        Notification::make()
                            ->title('تم بنجاح')
                            ->body("تم إرسال مهمة تفريغ صفحات العدد #{$record->issue_number} إلى طابور المعالجة.")
                            ->success()
                            ->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListIssues::route('/'),
            'create' => CreateIssue::route('/create'),
            'edit' => EditIssue::route('/{record}/edit'),
        ];
    }
}
