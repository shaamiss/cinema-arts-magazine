<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|UnitEnum|null $navigationGroup = 'إدارة النظام';

    protected static ?string $modelLabel = 'مستخدم وهيئة التحرير';

    protected static ?string $pluralModelLabel = 'المستخدمون والصلاحيات';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    /**
     * Strictly restrict access to Super Administrators.
     */
    public static function canViewAny(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) User::count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('معلومات الحساب والولوج')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('name')
                                ->label('الاسم الكامل')
                                ->required()
                                ->maxLength(255),

                            TextInput::make('email')
                                ->label('البريد الإلكتروني المهني')
                                ->email()
                                ->required()
                                ->maxLength(255)
                                ->unique(ignoreRecord: true),
                        ]),

                        TextInput::make('password')
                            ->label('كلمة المرور')
                            ->password()
                            ->revealable()
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->helperText('اتركه فارغاً عند التعديل للاحتفاظ بكلمة المرور الحالية.'),
                    ]),

                Section::make('الأدوار والصلاحيات التحريرية')
                    ->description('تحديد الصلاحيات وتعيين الأقسام لمحرري الأقسام')
                    ->schema([
                        Select::make('roles')
                            ->label('الأدوار النظامية (Roles)')
                            ->relationship('roles', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->helperText('الأدوار المعتمدة: super-admin, chief-editor, section-editor, media-producer.'),

                        Select::make('managedCategories')
                            ->label('الأقسام الخاضعة للإشراف التحريري')
                            ->relationship('managedCategories', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->helperText('خاص بمحرري الأقسام (Section Editors) للتحكم بالمقالات التابعة لتخصصهم.'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('الاسم')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('email')
                    ->label('البريد الإلكتروني')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('roles.name')
                    ->label('الدور التحريري')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'super-admin' => 'danger',
                        'chief-editor' => 'warning',
                        'section-editor' => 'info',
                        'media-producer' => 'primary',
                        default => 'gray',
                    }),

                TextColumn::make('managedCategories.name')
                    ->label('الأقسام المسندة')
                    ->badge()
                    ->color('success')
                    ->placeholder('-'),

                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->date('Y-m-d')
                    ->sortable(),
            ])
            ->defaultSort('name', 'asc')
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
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
