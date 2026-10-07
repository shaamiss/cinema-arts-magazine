<?php

namespace App\Filament\Widgets;

use App\Enums\PublicationStatus;
use App\Models\Article;
use App\Models\Issue;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $publishedIssuesCount = Issue::published()->count();
        $publishedArticlesCount = Article::published()->count();
        $totalViews = Article::sum('views_count');
        $pendingReviewCount = Article::where('status', PublicationStatus::Review)->count();

        return [
            Stat::make('الأعداد الدورية المنشورة', $publishedIssuesCount)
                ->description('إجمالي الأعداد الصادرة المتاحة للجمهور')
                ->descriptionIcon('heroicon-m-book-open')
                ->color('success'),

            Stat::make('المقالات والدراسات النشطة', $publishedArticlesCount)
                ->description('دراسات نقدية وبحوث معتمدة')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('primary'),

            Stat::make('إجمالي قراءات البوابة', number_format($totalViews))
                ->description('مشاهدات المقالات والدوريات')
                ->descriptionIcon('heroicon-m-eye')
                ->color('amber'),

            Stat::make('بانتظار المراجعة والاعتماد', $pendingReviewCount)
                ->description('مقالات محالة لهيئة التحرير')
                ->descriptionIcon('heroicon-m-clock')
                ->color($pendingReviewCount > 0 ? 'warning' : 'gray'),
        ];
    }
}
