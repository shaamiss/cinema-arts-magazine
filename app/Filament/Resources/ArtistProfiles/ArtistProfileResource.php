<?php

namespace App\Filament\Resources\ArtistProfiles;

use App\Enums\ArtCategory;
use App\Filament\Resources\ArtistProfiles\Pages\CreateArtistProfile;
use App\Filament\Resources\ArtistProfiles\Pages\EditArtistProfile;
use App\Filament\Resources\ArtistProfiles\Pages\ListArtistProfiles;
use App\Models\ArtistProfile;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use UnitEnum;

class ArtistProfileResource extends Resource
{
    protected static ?string $model = ArtistProfile::class;

    protected static string|UnitEnum|null $navigationGroup = 'الأرشيف الفني';

    protected static ?string $modelLabel = 'رائد / فنان';

    protected static ?string $pluralModelLabel = 'دليل الفنانين والرواد';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    public static function getNavigationBadge(): ?string
    {
        return (string) ArtistProfile::count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'info';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('البيانات الشخصية والمهنية')
                    ->description('توثيق هوية الفنان ومجال إبداعه الفني والمسرحي')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('full_name')
                                ->label('الاسم الكامل للرائد')
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
                        ]),

                        Grid::make(3)->schema([
                                Select::make('art_category')
                                    ->label('المجال والتخصص الإبداعي')
                                    ->options(ArtCategory::class)
                                    ->required(),

                                DatePicker::make('birth_date')
                                    ->label('تاريخ الميلاد')
                                    ->native(false)
                                    ->displayFormat('Y-m-d'),

                                DatePicker::make('death_date')
                                    ->label('تاريخ الوفاة (إن وجد)')
                                    ->native(false)
                                    ->displayFormat('Y-m-d')
                                    ->helperText('اتركه فارغاً للفنانين المعاصرين.'),
                        ]),

                        Toggle::make('is_featured')
                            ->label('تمييز الرائد في الصفحة الرئيسية')
                            ->helperText('يظهر في قسم رواد ومبدعو الحركة الفنية بالبوابة العامة.')
                            ->default(false),
                    ]),

                Section::make('الصورة والسيرة الذاتية')
                    ->schema([
                        FileUpload::make('avatar_url')
                                ->label('الصورة الشخصية / البورتريه')
                                ->image()
                                ->disk('public')
                                ->directory('artists')
                                ->visibility('public')
                                ->imageEditor()
                                ->circleCropper(),

                        RichEditor::make('biography')
                                ->label('السيرة الذاتية والمسيرة الفنية')
                                ->required()
                                
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('avatar_url')
                    ->label('الصورة')
                    ->disk('public')
                    ->circular()
                    ->defaultImageUrl('/images/placeholder-artist.jpg'),

                TextColumn::make('full_name')
                    ->label('اسم الفنان / الرائد')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('art_category')
                    ->label('المجال الفني')
                    ->badge()
                    ->color(fn (ArtCategory $state): string => match ($state) {
                        ArtCategory::Cinema => 'warning',
                        ArtCategory::Theatre => 'success',
                        ArtCategory::FineArts => 'info',
                        ArtCategory::Music => 'primary',
                    }),

                IconColumn::make('is_featured')
                    ->label('مميز')
                    ->boolean()
                    ->alignCenter(),

                TextColumn::make('articles_count')
                    ->counts('articles')
                    ->label('الدراسات الموثقة')
                    ->badge()
                    ->color('amber')
                    ->alignCenter(),

                TextColumn::make('birth_date')
                    ->label('الميلاد')
                    ->date('Y')
                    ->placeholder('-'),

                TextColumn::make('death_date')
                    ->label('الوفاة')
                    ->date('Y')
                    ->placeholder('معاصر'),
            ])
            ->defaultSort('full_name', 'asc')
            ->filters([
                SelectFilter::make('art_category')
                    ->label('المجال الفني')
                    ->options(ArtCategory::class),
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
            'index' => ListArtistProfiles::route('/'),
            'create' => CreateArtistProfile::route('/create'),
            'edit' => EditArtistProfile::route('/{record}/edit'),
        ];
    }
}
