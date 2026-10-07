<?php

namespace App\Filament\Widgets;

use App\Enums\PublicationStatus;
use App\Filament\Resources\Articles\ArticleResource;
use App\Models\Article;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestArticlesWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'أحدث المقالات المحالة للمراجعة والاعتماد';

    public function table(Table $table): Table
    {
        return $table
            ->query(function () {
                $reviewQuery = Article::query()->where('status', PublicationStatus::Review);

                if ($reviewQuery->exists()) {
                    return $reviewQuery->latest('updated_at')->limit(5);
                }

                return Article::query()->latest('updated_at')->limit(5);
            })
            ->columns([
                ImageColumn::make('featured_image')
                    ->label('الصورة')
                    ->disk('public')
                    ->square()
                    ->defaultImageUrl('/images/placeholder-article.jpg'),

                TextColumn::make('title')
                    ->label('عنوان المقال')
                    ->wrap()
                    ->weight('bold'),

                TextColumn::make('category.name')
                    ->label('القسم')
                    ->badge()
                    ->color('info'),

                TextColumn::make('author.name')
                    ->label('الكاتب / المحرر'),

                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->color(fn (PublicationStatus $state): string => match ($state) {
                        PublicationStatus::Published => 'success',
                        PublicationStatus::Review => 'warning',
                        PublicationStatus::Draft => 'gray',
                        PublicationStatus::Archived => 'danger',
                    }),

                TextColumn::make('updated_at')
                    ->label('آخر تحديث')
                    ->since(),
            ])
            ->recordUrl(fn (Article $record): string => ArticleResource::getUrl('edit', ['record' => $record]))
            ->paginated(false);
    }
}
