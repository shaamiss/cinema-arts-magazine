<script setup lang="ts">
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLayout from '../../../Layouts/AppLayout.vue';
import SeoHead from '../../../Components/SeoHead.vue';
import MagazineReader, { type IssueData } from '../../../Components/MagazineReader.vue';
import {
    BookOpen,
    Calendar,
    Layers,
    Download,
    Share2,
    Eye,
    User,
    ChevronLeft,
    Maximize2,
    FileText,
    ArrowRight
} from 'lucide-vue-next';

interface Article {
    id: number;
    title: string;
    slug: string;
    excerpt?: string | null;
    published_at?: string | null;
    views_count?: number;
    category?: { id: number; name: string; slug: string } | null;
    author?: { id: number; name: string } | null;
    artist_profile?: { id: number; full_name: string; slug: string } | null;
}

interface Issue {
    id: number;
    issue_number: number;
    title: string;
    slug: string;
    cover_image?: string | null;
    published_at?: string | null;
    pages_count: number;
    pdf_file_path?: string | null;
    articles: Article[];
}

interface ReaderData {
    issue: IssueData;
    pages: string[];
    thumbnails: string[];
}

const props = defineProps<{
    issue: Issue;
    readerData: ReaderData;
}>();

// Flipbook Reader Modal State
const isReaderOpen = ref(false);
const initialReaderPage = ref(1);

const openReader = (pageNumber = 1) => {
    initialReaderPage.value = pageNumber;
    isReaderOpen.value = true;
};

const closeReader = () => {
    isReaderOpen.value = false;
};

const formatDate = (dateStr?: string | null) => {
    if (!dateStr) return '';
    try {
        const d = new Date(dateStr);
        return d.toLocaleDateString('ar-LY', { year: 'numeric', month: 'long', day: 'numeric' });
    } catch {
        return dateStr;
    }
};
</script>

<template>
    <AppLayout>
        <SeoHead
            :title="`العدد #${issue.issue_number}: ${issue.title}`"
            :description="`تصفح العدد رقم ${issue.issue_number} من مجلة الفنون والسينما: ${issue.title}. قراءة رقمية تفاعلية وفهرس المقالات والدراسات.`"
            :image="issue.cover_image ? (issue.cover_image.startsWith('http') ? issue.cover_image : `/storage/${issue.cover_image}`) : null"
            type="issue"
            :issue-number="issue.issue_number"
            :published-at="issue.published_at"
        />

        <!-- Fullscreen / Modal Digital Flipbook Reader -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition duration-300 ease-out"
                enter-from-class="opacity-0 scale-95"
                enter-to-class="opacity-100 scale-100"
                leave-active-class="transition duration-200 ease-in"
                leave-from-class="opacity-100 scale-100"
                leave-to-class="opacity-0 scale-95"
            >
                <div v-if="isReaderOpen" class="fixed inset-0 z-50 bg-black">
                    <MagazineReader
                        :issue="readerData.issue"
                        :pages="readerData.pages"
                        :thumbnails="readerData.thumbnails"
                        :initial-page="initialReaderPage"
                        @close="closeReader"
                    />
                </div>
            </Transition>
        </Teleport>

        <div class="py-10 bg-neutral-950 min-h-screen">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
                
                <!-- Breadcrumbs -->
                <nav class="flex items-center gap-2 text-xs font-heading text-neutral-400">
                    <Link href="/" class="hover:text-amber-400 transition-colors">الرئيسية</Link>
                    <span>/</span>
                    <Link href="/issues" class="hover:text-amber-400 transition-colors">أرشيف الأعداد</Link>
                    <span>/</span>
                    <span class="text-amber-500 font-bold">العدد #{{ issue.issue_number }}</span>
                </nav>

                <!-- Issue Hero Presentation -->
                <div class="rounded-3xl bg-neutral-900/80 border border-neutral-800 p-6 sm:p-10 lg:p-12 relative overflow-hidden">
                    <!-- Subtle Glow -->
                    <div class="absolute -top-24 -right-24 w-96 h-96 bg-amber-600/10 rounded-full blur-3xl pointer-events-none"></div>

                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-center relative z-10">
                        
                        <!-- Left on desktop (RTL Cover Mockup) -->
                        <div class="lg:col-span-4 flex justify-center">
                            <div class="relative group max-w-xs w-full">
                                <div class="aspect-[3/4] rounded-2xl overflow-hidden bg-neutral-950 border border-neutral-700/80 shadow-2xl relative">
                                    <img
                                        v-if="issue.cover_image"
                                        :src="issue.cover_image.startsWith('http') ? issue.cover_image : `/storage/${issue.cover_image}`"
                                        :alt="issue.title"
                                        class="w-full h-full object-cover"
                                    />
                                    <div v-else class="w-full h-full flex flex-col items-center justify-center p-6 bg-neutral-900 text-center">
                                        <BookOpen class="w-16 h-16 text-neutral-700 mb-3" />
                                        <span class="text-sm font-heading font-bold text-neutral-300">{{ issue.title }}</span>
                                    </div>

                                    <!-- Quick Open Reader Overlay -->
                                    <button
                                        type="button"
                                        @click="openReader(1)"
                                        class="absolute inset-0 bg-neutral-950/60 opacity-0 group-hover:opacity-100 backdrop-blur-xs flex flex-col items-center justify-center gap-2 text-white font-heading font-bold text-sm transition-opacity duration-300"
                                    >
                                        <Maximize2 class="w-8 h-8 text-amber-400" />
                                        <span>تصفح بالمستعرض الرقمي</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Right on desktop (Issue Metadata & Actions) -->
                        <div class="lg:col-span-8 space-y-6 text-right">
                            <div class="space-y-3">
                                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-500/10 border border-amber-500/20 text-amber-400 text-xs font-semibold font-heading">
                                    <span>العدد رقم {{ issue.issue_number }}</span>
                                    <span>•</span>
                                    <span>الإصدار الرسمي</span>
                                </div>

                                <h1 class="text-2xl sm:text-3xl lg:text-4xl font-heading font-black text-white leading-tight">
                                    {{ issue.title }}
                                </h1>
                            </div>

                            <div class="flex flex-wrap items-center gap-6 text-sm text-neutral-400 pb-2 border-b border-neutral-800">
                                <span class="flex items-center gap-2">
                                    <Calendar class="w-4 h-4 text-amber-500" />
                                    تاريخ النشر: {{ formatDate(issue.published_at) }}
                                </span>
                                <span class="flex items-center gap-2">
                                    <Layers class="w-4 h-4 text-neutral-400" />
                                    عدد الصفحات: {{ issue.pages_count }} صفحة
                                </span>
                                <span class="flex items-center gap-2">
                                    <FileText class="w-4 h-4 text-neutral-400" />
                                    {{ issue.articles?.length || 0 }} مقالاً ودراسة
                                </span>
                            </div>

                            <p class="text-neutral-300 text-sm sm:text-base leading-relaxed font-light">
                                يتناول هذا العدد ملفات توثيقية ونقدية متخصصة ومقالات بأقلام باحثين ليبيين وعرب، مع صور أرشيفية نادرة وتغطية لأبرز الفعاليات المسرحية والسينمائية.
                            </p>

                            <!-- Primary Interaction Buttons -->
                            <div class="pt-4 flex flex-wrap items-center gap-4">
                                <button
                                    type="button"
                                    @click="openReader(1)"
                                    class="inline-flex items-center gap-2 px-6 py-3.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-neutral-950 font-heading font-bold text-sm transition-all shadow-lg shadow-amber-500/20"
                                >
                                    <BookOpen class="w-5 h-5 text-neutral-950" />
                                    <span>تصفح العدد بالكامل (المستعرض الرقمي)</span>
                                </button>

                                <a
                                    v-if="readerData.issue.pdf_url || issue.pdf_file_path"
                                    :href="readerData.issue.pdf_url || `/storage/${issue.pdf_file_path}`"
                                    target="_blank"
                                    class="inline-flex items-center gap-2 px-5 py-3.5 rounded-xl bg-neutral-800 hover:bg-neutral-700 text-white font-heading font-semibold text-sm transition-all"
                                >
                                    <Download class="w-4 h-4 text-amber-400" />
                                    <span>تحميل النسخة الكاملة PDF</span>
                                </a>
                            </div>

                        </div>

                    </div>
                </div>

                <!-- Table of Contents Section (فهرس محتويات العدد) -->
                <div class="space-y-6">
                    <div class="flex items-center justify-between pb-3 border-b border-neutral-800">
                        <div>
                            <span class="text-xs font-semibold text-amber-500 uppercase tracking-widest font-heading">محتويات الإصدار</span>
                            <h2 class="text-xl sm:text-2xl font-heading font-bold text-white mt-1">
                                فهرس الدراسات والمقالات
                            </h2>
                        </div>
                        <span class="text-xs text-neutral-400 font-heading">
                            {{ issue.articles?.length || 0 }} مادة منشورة
                        </span>
                    </div>

                    <!-- Articles Table of Contents Grid -->
                    <div v-if="issue.articles && issue.articles.length" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <Link
                            v-for="(art, idx) in issue.articles"
                            :key="art.id"
                            :href="`/articles/${art.slug}`"
                            class="group p-5 rounded-2xl bg-neutral-900/70 border border-neutral-800 hover:border-amber-500/40 hover:bg-neutral-900 transition-all flex items-start gap-4"
                        >
                            <span class="w-8 h-8 rounded-xl bg-neutral-800 text-amber-400 group-hover:bg-amber-500 group-hover:text-neutral-950 flex items-center justify-center font-heading font-bold text-xs shrink-0 transition-colors">
                                {{ idx + 1 }}
                            </span>

                            <div class="flex-1 space-y-1.5">
                                <div class="flex items-center gap-2 text-xs">
                                    <span v-if="art.category" class="font-heading font-semibold text-amber-400">
                                        {{ art.category.name }}
                                    </span>
                                    <span v-if="art.author" class="text-neutral-400">
                                        • {{ art.author.name }}
                                    </span>
                                </div>

                                <h3 class="font-heading font-bold text-base text-white group-hover:text-amber-400 transition-colors leading-snug">
                                    {{ art.title }}
                                </h3>

                                <p v-if="art.excerpt" class="text-xs text-neutral-400 line-clamp-2 font-light">
                                    {{ art.excerpt }}
                                </p>
                            </div>

                            <ChevronLeft class="w-4 h-4 text-neutral-600 group-hover:text-amber-400 group-hover:-translate-x-1 transition-all shrink-0 mt-1" />
                        </Link>
                    </div>

                    <div v-else class="py-12 text-center rounded-2xl bg-neutral-900/50 border border-neutral-800 space-y-2">
                        <FileText class="w-8 h-8 text-neutral-600 mx-auto" />
                        <p class="text-sm text-neutral-400">لم يتم إدراج مقالات مفردة لهذا العدد، يرجى تصفح نسخة المجلة الكاملة عبر المستعرض الرقمي.</p>
                    </div>
                </div>

            </div>
        </div>
    </AppLayout>
</template>
