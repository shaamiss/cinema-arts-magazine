<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '../../../Layouts/AppLayout.vue';
import SeoHead from '../../../Components/SeoHead.vue';
import {
    BookOpen,
    Calendar,
    Layers,
    FileText,
    ChevronLeft,
    Filter,
    Download
} from 'lucide-vue-next';

interface Issue {
    id: number;
    issue_number: number;
    title: string;
    slug: string;
    cover_image?: string | null;
    published_at?: string | null;
    pages_count: number;
    articles_count?: number;
    pdf_file_path?: string | null;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface IssuesPagination {
    data: Issue[];
    links: PaginationLink[];
    current_page: number;
    last_page: number;
    total: number;
}

const props = defineProps<{
    issues: IssuesPagination;
    availableYears: number[];
    currentYear: number | null;
}>();

const filterByYear = (year: number | null) => {
    router.get('/issues', year ? { year } : {}, { preserveState: true, preserveScroll: true });
};

const formatDate = (dateStr?: string | null) => {
    if (!dateStr) return '';
    try {
        const d = new Date(dateStr);
        return d.toLocaleDateString('ar-LY', { year: 'numeric', month: 'long' });
    } catch {
        return dateStr;
    }
};
</script>

<template>
    <AppLayout>
        <SeoHead
            title="أرشيف أعداد المجلة | مجلة الفنون والسينما"
            description="الأرشيف الكامل لأعداد مجلة الفنون والسينما الصادرة عن الهيئة العامة للسينما والمسرح والفنون. تصفح رقمي عالي الدقة لكافة الإصدارات."
            type="website"
        />

        <div class="py-12 bg-neutral-950 min-h-screen">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
                
                <!-- Page Header & Cultural Breadcrumb -->
                <div class="border-b border-neutral-800 pb-8 flex flex-col md:flex-row md:items-end justify-between gap-6">
                    <div class="space-y-3">
                        <div class="inline-flex items-center gap-2 text-xs font-heading text-amber-500">
                            <Link href="/" class="hover:underline">الرئيسية</Link>
                            <span>/</span>
                            <span class="text-neutral-400">أرشيف الأعداد</span>
                        </div>
                        <h1 class="text-3xl sm:text-4xl font-heading font-black text-white">
                            أرشيف أعداد المجلة
                        </h1>
                        <p class="text-sm sm:text-base text-neutral-400 font-light max-w-2xl">
                            سجل الدوريات الثقافية المحكمة الصادرة عن الهيئة العامة للسينما والمسرح والفنون. متوفرة للقراءة عبر المستعرض التفاعلي وللتحميل المباشر.
                        </p>
                    </div>

                    <!-- Year Filters -->
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="text-xs text-neutral-400 flex items-center gap-1.5 ml-2 font-heading">
                            <Filter class="w-3.5 h-3.5 text-amber-500" />
                            السنة:
                        </span>
                        
                        <button
                            type="button"
                            @click="filterByYear(null)"
                            class="px-3 py-1.5 rounded-lg text-xs font-heading font-semibold transition-colors"
                            :class="[
                                currentYear === null
                                    ? 'bg-amber-500 text-neutral-950'
                                    : 'bg-neutral-900 border border-neutral-800 text-neutral-300 hover:text-white'
                            ]"
                        >
                            كافة السنوات
                        </button>

                        <button
                            v-for="year in availableYears"
                            :key="year"
                            type="button"
                            @click="filterByYear(year)"
                            class="px-3 py-1.5 rounded-lg text-xs font-heading font-semibold transition-colors"
                            :class="[
                                currentYear === year
                                    ? 'bg-amber-500 text-neutral-950'
                                    : 'bg-neutral-900 border border-neutral-800 text-neutral-300 hover:text-white'
                            ]"
                        >
                            {{ year }}
                        </button>
                    </div>
                </div>

                <!-- Issues Grid -->
                <div v-if="issues.data.length" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-8">
                    <div
                        v-for="issue in issues.data"
                        :key="issue.id"
                        class="group rounded-2xl bg-neutral-900/80 border border-neutral-800 hover:border-amber-500/40 p-4 transition-all duration-300 hover:-translate-y-1 flex flex-col justify-between"
                    >
                        <div>
                            <!-- Cover Mockup Frame -->
                            <div class="relative aspect-[3/4] rounded-xl overflow-hidden bg-neutral-950 border border-neutral-800/80 shadow-md mb-4">
                                <img
                                    v-if="issue.cover_image"
                                    :src="issue.cover_image.startsWith('http') ? issue.cover_image : `/storage/${issue.cover_image}`"
                                    :alt="issue.title"
                                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                />
                                <div v-else class="w-full h-full flex flex-col items-center justify-center p-4 bg-neutral-950 text-neutral-600 text-center">
                                    <BookOpen class="w-10 h-10 text-neutral-700 mb-2" />
                                    <span class="text-xs font-heading font-bold text-neutral-400">{{ issue.title }}</span>
                                </div>

                                <!-- Issue Tag Badge -->
                                <div class="absolute top-3 right-3 px-2.5 py-1 rounded-md bg-neutral-950/90 backdrop-blur-md border border-neutral-700 text-xs font-heading font-bold text-amber-400">
                                    العدد #{{ issue.issue_number }}
                                </div>
                            </div>

                            <!-- Issue Details -->
                            <div class="space-y-2">
                                <div class="flex items-center justify-between text-xs text-neutral-400">
                                    <span class="flex items-center gap-1">
                                        <Calendar class="w-3.5 h-3.5 text-amber-500" />
                                        {{ formatDate(issue.published_at) }}
                                    </span>
                                    <span class="flex items-center gap-1">
                                        <Layers class="w-3.5 h-3.5 text-neutral-400" />
                                        {{ issue.pages_count }} صفحة
                                    </span>
                                </div>

                                <h3 class="font-heading font-bold text-base text-white group-hover:text-amber-400 transition-colors line-clamp-2 leading-snug">
                                    {{ issue.title }}
                                </h3>

                                <p v-if="issue.articles_count !== undefined" class="text-xs text-neutral-400">
                                    يتضمن {{ issue.articles_count }} مقالاً ودراسة موثقة
                                </p>
                            </div>
                        </div>

                        <!-- Read & Action Buttons -->
                        <div class="pt-4 mt-4 border-t border-neutral-800/80 flex items-center gap-2">
                            <Link
                                :href="`/issues/${issue.slug}`"
                                class="flex-1 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-neutral-950 font-heading font-bold text-xs text-center transition-colors flex items-center justify-center gap-1.5"
                            >
                                <BookOpen class="w-3.5 h-3.5" />
                                <span>تصفح العدد</span>
                            </Link>

                            <a
                                v-if="issue.pdf_file_path"
                                :href="`/storage/${issue.pdf_file_path}`"
                                target="_blank"
                                class="p-2.5 rounded-xl bg-neutral-800 hover:bg-neutral-700 text-neutral-300 hover:text-white transition-colors"
                                title="تحميل ملف PDF"
                            >
                                <Download class="w-3.5 h-3.5" />
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="py-20 text-center rounded-2xl bg-neutral-900/50 border border-neutral-800 space-y-4">
                    <BookOpen class="w-12 h-12 text-neutral-600 mx-auto" />
                    <h3 class="font-heading font-bold text-white text-lg">لم يتم العثور على أعداد</h3>
                    <p class="text-sm text-neutral-400">جرب اختيار سنة أخرى أو إعادة تعيين عامل التصفية</p>
                    <button
                        type="button"
                        @click="filterByYear(null)"
                        class="px-4 py-2 rounded-xl bg-amber-500 text-neutral-950 font-heading font-semibold text-xs"
                    >
                        عرض كافة الأعداد
                    </button>
                </div>

                <!-- Pagination Links -->
                <div v-if="issues.links && issues.links.length > 3" class="pt-8 flex justify-center">
                    <div class="flex items-center gap-1.5 p-1 rounded-2xl bg-neutral-900 border border-neutral-800">
                        <template v-for="(link, i) in issues.links" :key="i">
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                class="px-3.5 py-1.5 rounded-xl text-xs font-heading font-semibold transition-colors"
                                :class="[
                                    link.active
                                        ? 'bg-amber-500 text-neutral-950 font-bold'
                                        : 'text-neutral-400 hover:text-white hover:bg-neutral-800'
                                ]"
                                v-html="link.label"
                            />
                            <span
                                v-else
                                class="px-3 py-1.5 text-xs text-neutral-600 cursor-not-allowed"
                                v-html="link.label"
                            />
                        </template>
                    </div>
                </div>

            </div>
        </div>
    </AppLayout>
</template>
