<?php

namespace App\Filament\Resources\Articles;

use App\Enums\PublicationStatus;
use App\Filament\Resources\Articles\Pages\CreateArticle;
use App\Filament\Resources\Articles\Pages\EditArticle;
use App\Filament\Resources\Articles\Pages\ListArticles;
use App\Models\Article;
use App\Models\Category;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use UnitEnum;

class ArticleResource extends Resource
{
    protected static ?string $model = Article::class;

    protected static string|UnitEnum|null $navigationGroup = 'التحرير والنشر';

    protected static ?string $modelLabel = 'مقال ودراسة';

    protected static ?string $pluralModelLabel = 'المقالات والدراسات';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    /**
     * Display pending review count as a badge for Chief Editor and Super Admin.
     */
    public static function getNavigationBadge(): ?string
    {
        $user = auth()->user();

        if ($user && ($user->isChiefEditor() || $user->isSuperAdmin())) {
            $count = Article::where('status', PublicationStatus::Review)->count();

            return $count > 0 ? (string) $count : null;
        }

        return null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    /**
     * Automatically scope Eloquent query so Section Editors only access articles within their assigned categories.
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if (! $user) {
            return $query;
        }

        if ($user->isSuperAdmin() || $user->isChiefEditor()) {
            return $query;
        }

        if ($user->isSectionEditor()) {
            $categoryIds = $user->managedCategories()->pluck('categories.id')->toArray();

            return $query->where(function ($q) use ($categoryIds, $user) {
                $q->whereIn('category_id', $categoryIds)
                    ->orWhere('author_id', $user->id);
            });
        }

        return $query->where('author_id', $user->id);
    }

    public static function form(Schema $schema): Schema
    {
        $user = auth()->user();
        $canPublish = $user && ($user->isSuperAdmin() || $user->isChiefEditor() || $user->can('articles.publish'));

        return $schema
            ->components([
                Section::make('المحتوى والعنوان')
                    ->description('عنوان المقال، الرابط الثابت، والتنضيد التحريري')
                    ->schema([
                        TextInput::make('title')
                            ->label('عنوان المقال')
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

                        Textarea::make('excerpt')
                            ->label('ملخص المقال / الإيجاز الصحفي')
                            ->rows(3)
                            ->maxLength(500)
                            ->helperText('موجز يظهر في الصفحة الرئيسية ومقدمة القراءة وبطاقات البحث.'),

                        RichEditor::make('content')
                            ->label('متن المقال والدراسة')
                            ->required()
                            ->fileAttachmentsDisk('public')
                            ->fileAttachmentsDirectory('articles/attachments')
                            ->fileAttachmentsVisibility('public')
                            ->toolbarButtons([
                               'bold',
        'italic',
        'h2',
        'h3',
        'bulletList',
        'orderedList',
        'blockquote',
        'link',
        'undo',
        'redo',
                            ]),
                    ]),

                Section::make('التصنيف والارتباطات التحريرية')
                    ->description('تحديد القسم الثقافي والعدد الدوري والفنان المتناول')
                    ->schema([
                        Grid::make(3)->schema([
                            Select::make('category_id')
                                ->label('القسم / المسار الثقافي')
                                ->options(function () {
                                    return Category::with('parent')
                                        ->get()
                                        ->mapWithKeys(function ($cat) {
                                            $prefix = $cat->parent ? "{$cat->parent->name} > " : '';

                                            return [$cat->id => "{$prefix}{$cat->name}"];
                                        });
                                })
                                ->searchable()
                                ->required(),

                            Select::make('issue_id')
                                ->label('العدد الدوري التابع له')
                                ->relationship('issue', 'title')
                                ->searchable()
                                ->preload()
                                ->nullable()
                                ->helperText('اختياري في حال نشر المقال بشكل رقمي مستقل.'),

                            Select::make('artist_profile_id')
                                ->label('ملف الرائد / الفنان المتناول')
                                ->relationship('artistProfile', 'full_name')
                                ->searchable()
                                ->preload()
                                ->nullable()
                                ->helperText('ربط المقال بملف الفنان لتوثيق أعماله وسيرته.'),
                        ]),

                        Select::make('author_id')
                            ->label('الكاتب / الباحث')
                            ->relationship('author', 'name')
                            ->default(fn () => auth()->id())
                            ->required()
                            ->searchable(),
                    ]),

                Section::make('الصورة البارزة والإعلام')
                    ->schema([
                        FileUpload::make('featured_image')
                            ->label('الصورة البارزة للغلاف')
                            ->image()
                            ->disk('public')
                            ->directory('articles')
                            ->visibility('public')
                            ->imageEditor()
                            ->helperText('الصورة الرئيسية التي تتصدر المقال في البوابة ومشاركات التواصل الاجتماعي.'),
                    ]),

                Section::make('حالة النشر والاعتماد')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('status')
                                ->label('حالة النشر')
                                ->options(function () use ($canPublish) {
                                    if ($canPublish) {
                                        return [
                                            PublicationStatus::Draft->value => 'مسودة (Draft)',
                                            PublicationStatus::Review->value => 'مراجعة تحريرية (In Review)',
                                            PublicationStatus::Published->value => 'منشور معتمد (Published)',
                                            PublicationStatus::Archived->value => 'مؤرشف (Archived)',
                                        ];
                                    }

                                    return [
                                        PublicationStatus::Draft->value => 'مسودة (Draft)',
                                        PublicationStatus::Review->value => 'إرسال للمراجعة والاعتماد (Submit for Review)',
                                    ];
                                })
                                ->default(PublicationStatus::Draft)
                                ->required()
                                ->helperText($canPublish ? 'لديك صلاحية النشر المباشر.' : 'يمكنك حفظ كمسودة أو إرسال للمراجعة.'),

                            DatePicker::make('published_at')
                                ->label('تاريخ النشر المعتمد')
                                ->native(false)
                                ->displayFormat('Y-m-d')
                                ->default(now()),
                        ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('featured_image')
                    ->label('الصورة')
                    ->disk('public')
                    ->square()
                    ->defaultImageUrl('/images/placeholder-article.jpg'),

                TextColumn::make('title')
                    ->label('عنوان المقال')
                    ->searchable()
                    ->wrap()
                    ->weight('bold'),

                TextColumn::make('category.name')
                    ->label('القسم')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                TextColumn::make('author.name')
                    ->label('الكاتب')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('issue.issue_number')
                    ->label('العدد')
                    ->badge()
                    ->color('amber')
                    ->default('-')
                    ->alignCenter(),

                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->color(fn (PublicationStatus $state): string => match ($state) {
                        PublicationStatus::Published => 'success',
                        PublicationStatus::Review => 'warning',
                        PublicationStatus::Draft => 'gray',
                        PublicationStatus::Archived => 'danger',
                    }),

                TextColumn::make('views_count')
                    ->label('المشاهدات')
                    ->numeric()
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('published_at')
                    ->label('تاريخ النشر')
                    ->date('Y-m-d')
                    ->sortable(),
            ])
            ->defaultSort('published_at', 'desc')
            ->filters([
                SelectFilter::make('category_id')
                    ->label('القسم الثقافي')
                    ->relationship('category', 'name'),

                SelectFilter::make('status')
                    ->label('حالة النشر')
                    ->options(PublicationStatus::class),

                SelectFilter::make('issue_id')
                    ->label('العدد الدوري')
                    ->relationship('issue', 'title'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
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
            'index' => ListArticles::route('/'),
            'create' => CreateArticle::route('/create'),
            'edit' => EditArticle::route('/{record}/edit'),
        ];
    }
}
