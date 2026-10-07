<?php

namespace App\Filament\Resources\MediaGalleries;

use App\Enums\MediaType;
use App\Filament\Resources\MediaGalleries\Pages\CreateMediaGallery;
use App\Filament\Resources\MediaGalleries\Pages\EditMediaGallery;
use App\Filament\Resources\MediaGalleries\Pages\ListMediaGalleries;
use App\Models\MediaGallery;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\FileUpload;
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
use Illuminate\Support\Str;
use UnitEnum;

class MediaGalleryResource extends Resource
{
    protected static ?string $model = MediaGallery::class;

    protected static string|UnitEnum|null $navigationGroup = 'الأرشيف الفني';

    protected static ?string $modelLabel = 'عنصر وسائط';

    protected static ?string $pluralModelLabel = 'المعرض والوسائط الرقمية';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    public static function getNavigationBadge(): ?string
    {
        return (string) MediaGallery::count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'gray';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('معلومات ملف الوسائط')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('title')
                                ->label('عنوان الوسيط / الصورة')
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
                            Select::make('type')
                                ->label('نوع الوسائط')
                                ->options(MediaType::class)
                                ->required()
                                ->live(),

                            TextInput::make('duration_seconds')
                                ->label('المدة بالثواني (للمرئيات والصوت)')
                                ->numeric()
                                ->nullable()
                                ->visible(fn (callable $get) => in_array($get('type'), ['video', 'audio', MediaType::Video, MediaType::Audio])),

                            TextInput::make('size_bytes')
                                ->label('حجم الملف (بالبايت)')
                                ->numeric()
                                ->nullable()
                                ->helperText('يتم استخراجه تلقائياً عند رفع الملف.'),
                        ]),

                        Textarea::make('description')
                            ->label('وصف الأرشيف والبيانات الوثائقية')
                            ->rows(3)
                            ->maxLength(500),
                    ]),

                Section::make('الملف والمصغرة')
                    ->schema([
                        FileUpload::make('file_path')
                            ->label('الملف الرقمي الأصلي')
                            ->disk('public')
                            ->directory('media')
                            ->visibility('public')
                            ->required()
                            ->maxSize(102400) // 100MB
                            ->acceptedFileTypes([
                                'image/jpeg',
                                'image/png',
                                'image/webp',
                                'image/gif',
                                'video/mp4',
                                'video/webm',
                                'audio/mpeg',
                                'audio/mp3',
                                'audio/wav',
                                'audio/ogg',
                                'application/pdf',
                            ]),

                        FileUpload::make('thumbnail_path')
                            ->label('صورة المعاينة المصغرة (Thumbnail)')
                            ->image()
                            ->disk('public')
                            ->directory('media/thumbnails')
                            ->visibility('public')
                            ->imageEditor()
                            ->nullable()
                            ->helperText('مخصصة لملفات الفيديو أو الصوت لتظهر كغلاف في المعرض.'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('thumbnail_path')
                    ->label('المعاينة')
                    ->disk('public')
                    ->square()
                    ->defaultImageUrl(fn (MediaGallery $record) => $record->type === MediaType::Image
                        ? ($record->file_path ? "/storage/{$record->file_path}" : '/images/placeholder-media.jpg')
                        : '/images/placeholder-media.jpg'
                    ),

                TextColumn::make('title')
                    ->label('العنوان')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->weight('bold'),

                TextColumn::make('type')
                    ->label('نوع الوسيط')
                    ->badge()
                    ->color(fn (MediaType $state): string => match ($state) {
                        MediaType::Image => 'info',
                        MediaType::Video => 'warning',
                        MediaType::Audio => 'primary',
                        MediaType::Pdf => 'danger',
                    }),

                TextColumn::make('duration_seconds')
                    ->label('المدة')
                    ->formatStateUsing(function ($state) {
                        if (! $state) {
                            return '-';
                        }
                        $mins = floor($state / 60);
                        $secs = $state % 60;

                        return sprintf('%d:%02d دقيقة', $mins, $secs);
                    })
                    ->alignCenter(),

                TextColumn::make('size_bytes')
                    ->label('الحجم')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state / (1024 * 1024), 2).' MB' : '-')
                    ->alignCenter(),

                TextColumn::make('created_at')
                    ->label('تاريخ الإضافة')
                    ->date('Y-m-d')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('type')
                    ->label('النوع')
                    ->options(MediaType::class),
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
            'index' => ListMediaGalleries::route('/'),
            'create' => CreateMediaGallery::route('/create'),
            'edit' => EditMediaGallery::route('/{record}/edit'),
        ];
    }
}
