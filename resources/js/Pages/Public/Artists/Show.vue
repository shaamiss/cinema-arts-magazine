<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppLayout from '../../../Layouts/AppLayout.vue';
import SeoHead from '../../../Components/SeoHead.vue';
import {
    Calendar,
    BookOpen,
    ChevronLeft,
    Tag,
    User,
    ArrowRight,
    FileText
} from 'lucide-vue-next';

interface Article {
    id: number;
    title: string;
    slug: string;
    excerpt?: string | null;
    published_at?: string | null;
    category?: { id: number; name: string; slug: string } | null;
    issue?: { id: number; title: string; issue_number: number; slug: string } | null;
}

interface Artist {
    id: number;
    full_name: string;
    slug: string;
    art_category: string;
    biography?: string | null;
    avatar_url?: string | null;
    birth_date?: string | null;
    death_date?: string | null;
    is_featured: boolean;
}

const props = defineProps<{
    artist: Artist;
    articles: Article[];
}>();

const formatYears = (birth?: string | null, death?: string | null) => {
    if (!birth && !death) return '';
    const b = birth ? new Date(birth).getFullYear() : '';
    const d = death ? new Date(death).getFullYear() : 'معاصر';
    return `${b} - ${d}`;
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

const getCategoryLabel = (category: string) => {
    switch (category) {
        case 'cinema': return 'السينما والشاشة';
        case 'theatre': return 'المسرح وفنون الأداء';
        case 'fine_arts':
        case 'fine-arts': return 'الفنون التشكيلية';
        case 'music': return 'الموسيقى والمألوف';
        default: return category;
    }
};
</script>

<template>
    <AppLayout>
        <SeoHead
            :title="`${artist.full_name} | دليل الرواد`"
            :description="`سيرة وتوثيق أعمال ${artist.full_name} في مجال ${artist.art_category}. مقالات ودراسات نقدية منشورة في مجلة الفنون والسينما.`"
            :image="artist.avatar_url ? (artist.avatar_url.startsWith('http') ? artist.avatar_url : `/storage/${artist.avatar_url}`) : null"
            type="article"
        />

        <div class="py-10 bg-neutral-950 min-h-screen">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
                
                <!-- Breadcrumbs -->
                <nav class="flex items-center gap-2 text-xs font-heading text-neutral-400">
                    <Link href="/" class="hover:text-amber-400 transition-colors">الرئيسية</Link>
                    <span>/</span>
                    <Link href="/artists" class="hover:text-amber-400 transition-colors">دليل الرواد والفنانين</Link>
                    <span>/</span>
                    <span class="text-amber-500 font-bold truncate max-w-[200px]">{{ artist.full_name }}</span>
                </nav>

                <!-- Profile Hero Box -->
                <div class="rounded-3xl bg-neutral-900/80 border border-neutral-800 p-8 sm:p-12 relative overflow-hidden">
                    <div class="flex flex-col md:flex-row items-center md:items-start gap-8 relative z-10 text-right">
                        
                        <!-- Portrait Image -->
                        <div class="relative w-36 h-36 sm:w-44 sm:h-44 rounded-2xl overflow-hidden bg-neutral-950 border-2 border-neutral-700 shadow-2xl shrink-0">
                            <img
                                v-if="artist.avatar_url"
                                :src="artist.avatar_url.startsWith('http') ? artist.avatar_url : `/storage/${artist.avatar_url}`"
                                :alt="artist.full_name"
                                class="w-full h-full object-cover"
                            />
                            <div v-else class="w-full h-full flex items-center justify-center bg-gradient-to-br from-amber-600/20 to-neutral-900 text-amber-400 font-heading font-black text-4xl">
                                {{ artist.full_name.charAt(0) }}
                            </div>

                            <span
                                v-if="artist.is_featured"
                                class="absolute top-2 right-2 px-2 py-0.5 rounded bg-amber-500 text-neutral-950 font-heading font-bold text-[10px]"
                            >
                                رائد وطني
                            </span>
                        </div>

                        <!-- Artist Identity & Biography -->
                        <div class="space-y-4 flex-1">
                            <div class="space-y-1">
                                <span class="inline-block px-3 py-1 rounded-full bg-amber-500/10 border border-amber-500/20 text-amber-400 text-xs font-heading font-semibold">
                                    {{ getCategoryLabel(artist.art_category) }}
                                </span>
                                
                                <h1 class="text-3xl sm:text-4xl font-heading font-black text-white pt-2">
                                    {{ artist.full_name }}
                                </h1>

                                <p v-if="formatYears(artist.birth_date, artist.death_date)" class="text-sm text-neutral-400 font-medium">
                                    {{ formatYears(artist.birth_date, artist.death_date) }}
                                </p>
                            </div>

                            <div v-if="artist.biography" class="pt-2 text-neutral-300 text-sm sm:text-base leading-relaxed font-light">
                                {{ artist.biography }}
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Cataloged Works / Related Articles Section -->
                <section class="space-y-6">
                    <div class="flex items-center justify-between pb-3 border-b border-neutral-800">
                        <div>
                            <span class="text-xs font-semibold text-amber-500 uppercase tracking-widest font-heading">الأرشيف والدراسات</span>
                            <h2 class="text-2xl font-heading font-bold text-white mt-1">
                                المقالات والدراسات الموثقة
                            </h2>
                        </div>
                        <span class="text-xs text-neutral-400 font-heading">
                            {{ articles.length }} دراسات موثقة
                        </span>
                    </div>

                    <!-- Articles Grid -->
                    <div v-if="articles && articles.length" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <Link
                            v-for="article in articles"
                            :key="article.id"
                            :href="`/articles/${article.slug}`"
                            class="group p-6 rounded-2xl bg-neutral-900/70 border border-neutral-800 hover:border-amber-500/40 hover:bg-neutral-900 transition-all flex flex-col justify-between space-y-4"
                        >
                            <div class="space-y-2">
                                <div class="flex items-center gap-2 text-xs">
                                    <span v-if="article.category" class="font-heading font-semibold text-amber-400">
                                        {{ article.category.name }}
                                    </span>
                                    <span v-if="article.issue" class="text-neutral-500">
                                        • العدد #{{ article.issue.issue_number }}
                                    </span>
                                    <span class="text-neutral-500">
                                        • {{ formatDate(article.published_at) }}
                                    </span>
                                </div>

                                <h3 class="font-heading font-bold text-lg text-white group-hover:text-amber-400 transition-colors leading-snug">
                                    {{ article.title }}
                                </h3>

                                <p v-if="article.excerpt" class="text-xs text-neutral-400 line-clamp-3 font-light leading-relaxed">
                                    {{ article.excerpt }}
                                </p>
                            </div>

                            <div class="pt-3 border-t border-neutral-800/60 flex items-center justify-between text-xs text-neutral-400">
                                <span class="text-amber-500 font-medium flex items-center gap-1">
                                    قراءة الدراسة كاملة
                                    <ChevronLeft class="w-3.5 h-3.5 group-hover:-translate-x-1 transition-transform" />
                                </span>
                            </div>
                        </Link>
                    </div>

                    <!-- Empty state -->
                    <div v-else class="py-12 text-center rounded-2xl bg-neutral-900/50 border border-neutral-800 space-y-2">
                        <FileText class="w-8 h-8 text-neutral-600 mx-auto" />
                        <p class="text-sm text-neutral-400">لم يتم ربط مقالات نقدية منشورة بهذا الملف بعد.</p>
                    </div>
                </section>

            </div>
        </div>
    </AppLayout>
</template>
