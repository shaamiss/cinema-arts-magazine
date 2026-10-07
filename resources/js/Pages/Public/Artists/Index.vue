<script setup lang="ts">
import { ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '../../../Layouts/AppLayout.vue';
import SeoHead from '../../../Components/SeoHead.vue';
import {
    Users,
    Search,
    Filter,
    BookOpen,
    Calendar,
    ChevronLeft
} from 'lucide-vue-next';

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
    articles_count?: number;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface ArtistsPagination {
    data: Artist[];
    links: PaginationLink[];
    current_page: number;
    last_page: number;
    total: number;
}

interface CategoryOption {
    value: string;
    label: string;
}

const props = defineProps<{
    artists: ArtistsPagination;
    categories: CategoryOption[];
    filters: {
        category?: string | null;
        search?: string | null;
    };
}>();

const searchQuery = ref(props.filters.search || '');
const selectedCategory = ref(props.filters.category || '');

const applyFilters = () => {
    router.get(
        '/artists',
        {
            category: selectedCategory.value || undefined,
            search: searchQuery.value.trim() || undefined,
        },
        { preserveState: true, preserveScroll: true }
    );
};

let debounceTimer: any = null;
watch(searchQuery, () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        applyFilters();
    }, 400);
});

const selectCategory = (cat: string) => {
    selectedCategory.value = selectedCategory.value === cat ? '' : cat;
    applyFilters();
};

const formatYears = (birth?: string | null, death?: string | null) => {
    if (!birth && !death) return '';
    const b = birth ? new Date(birth).getFullYear() : '';
    const d = death ? new Date(death).getFullYear() : 'معاصر';
    return `${b} - ${d}`;
};

const getCategoryLabel = (category: string) => {
    switch (category) {
        case 'cinema': return 'السينما';
        case 'theatre': return 'المسرح';
        case 'fine_arts':
        case 'fine-arts': return 'الفنون التشكيلية';
        case 'music': return 'الموسيقى';
        default: return category;
    }
};
</script>

<template>
    <AppLayout>
        <SeoHead
            title="دليل الرواد والفنانين | مجلة الفنون والسينما"
            description="معجم ودليل توثيقي لرواد ومبدعي المسرح، السينما، الفنون التشكيلية والموسيقى في دولة ليبيا. سير ذاتية، أعمال موثقة، ودراسات نقدية."
            type="website"
        />

        <div class="py-12 bg-neutral-950 min-h-screen">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
                
                <!-- Page Title & Header -->
                <div class="border-b border-neutral-800 pb-8 flex flex-col md:flex-row md:items-end justify-between gap-6">
                    <div class="space-y-3">
                        <div class="inline-flex items-center gap-2 text-xs font-heading text-amber-500">
                            <Link href="/" class="hover:underline">الرئيسية</Link>
                            <span>/</span>
                            <span class="text-neutral-400">دليل الرواد والفنانين</span>
                        </div>
                        <h1 class="text-3xl sm:text-4xl font-heading font-black text-white">
                            دليل الرواد والمبدعين
                        </h1>
                        <p class="text-sm sm:text-base text-neutral-400 font-light max-w-2xl">
                            قاعدة بيانات وسجل وطني يوثق سير وأعمال قامات الفن الليبي في المسرح والسينما والموسيقى والفنون التشكيلية.
                        </p>
                    </div>

                    <!-- Search Input -->
                    <div class="relative w-full md:w-80">
                        <input
                            v-model="searchQuery"
                            type="search"
                            placeholder="ابحث بالاسم..."
                            class="w-full px-4 py-2.5 pr-10 rounded-xl bg-neutral-900 border border-neutral-800 text-neutral-100 placeholder-neutral-500 text-sm focus:outline-none focus:border-amber-500"
                        />
                        <Search class="w-4 h-4 text-neutral-500 absolute top-3.5 right-3.5 pointer-events-none" />
                    </div>
                </div>

                <!-- Category Filter Pills -->
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="text-xs text-neutral-400 font-heading ml-2">المجال الفني:</span>
                    <button
                        type="button"
                        @click="selectCategory('')"
                        class="px-3.5 py-1.5 rounded-xl text-xs font-heading font-semibold transition-colors"
                        :class="[
                            !selectedCategory
                                ? 'bg-amber-500 text-neutral-950'
                                : 'bg-neutral-900 border border-neutral-800 text-neutral-400 hover:text-white'
                        ]"
                    >
                        الكل ({{ artists.total }})
                    </button>

                    <button
                        v-for="cat in categories"
                        :key="cat.value"
                        type="button"
                        @click="selectCategory(cat.value)"
                        class="px-3.5 py-1.5 rounded-xl text-xs font-heading font-semibold transition-colors"
                        :class="[
                            selectedCategory === cat.value
                                ? 'bg-amber-500 text-neutral-950'
                                : 'bg-neutral-900 border border-neutral-800 text-neutral-400 hover:text-white'
                        ]"
                    >
                        {{ getCategoryLabel(cat.value) }}
                    </button>
                </div>

                <!-- Artists Grid -->
                <div v-if="artists.data.length" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                    <Link
                        v-for="artist in artists.data"
                        :key="artist.id"
                        :href="`/artists/${artist.slug}`"
                        class="group p-6 rounded-2xl bg-neutral-900/80 border border-neutral-800 hover:border-amber-500/40 hover:bg-neutral-900 transition-all duration-300 flex flex-col justify-between space-y-4 hover:-translate-y-1"
                    >
                        <div class="space-y-4 flex flex-col items-center text-center">
                            <!-- Avatar Portrait -->
                            <div class="relative w-28 h-28 rounded-full overflow-hidden bg-neutral-950 border-2 border-neutral-700 group-hover:border-amber-500 transition-colors shadow-lg">
                                <img
                                    v-if="artist.avatar_url"
                                    :src="artist.avatar_url.startsWith('http') ? artist.avatar_url : `/storage/${artist.avatar_url}`"
                                    :alt="artist.full_name"
                                    class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
                                />
                                <div v-else class="w-full h-full flex items-center justify-center bg-gradient-to-br from-neutral-800 to-neutral-900 text-amber-400 font-heading font-black text-2xl">
                                    {{ artist.full_name.charAt(0) }}
                                </div>

                                <div v-if="artist.is_featured" class="absolute bottom-0 inset-x-0 bg-amber-500 text-neutral-950 text-[10px] font-heading font-bold py-0.5">
                                    رائد مميز
                                </div>
                            </div>

                            <!-- Name & Category -->
                            <div class="space-y-1">
                                <h3 class="font-heading font-bold text-lg text-white group-hover:text-amber-400 transition-colors">
                                    {{ artist.full_name }}
                                </h3>

                                <div class="flex items-center justify-center gap-2">
                                    <span class="inline-block px-2.5 py-0.5 rounded-full bg-neutral-800 border border-neutral-700/60 text-xs font-heading text-amber-400">
                                        {{ getCategoryLabel(artist.art_category) }}
                                    </span>
                                </div>

                                <p v-if="formatYears(artist.birth_date, artist.death_date)" class="text-xs text-neutral-500">
                                    {{ formatYears(artist.birth_date, artist.death_date) }}
                                </p>
                            </div>

                            <!-- Bio Snippet -->
                            <p v-if="artist.biography" class="text-xs text-neutral-400 line-clamp-3 font-light leading-relaxed">
                                {{ artist.biography }}
                            </p>
                        </div>

                        <!-- Footer Info -->
                        <div class="pt-4 border-t border-neutral-800/80 flex items-center justify-between text-xs text-neutral-400">
                            <span class="flex items-center gap-1.5">
                                <BookOpen class="w-3.5 h-3.5 text-amber-500" />
                                {{ artist.articles_count || 0 }} دراسات موثقة
                            </span>

                            <span class="text-amber-400 group-hover:-translate-x-1 transition-transform flex items-center">
                                الملف
                                <ChevronLeft class="w-3.5 h-3.5" />
                            </span>
                        </div>
                    </Link>
                </div>

                <!-- Empty State -->
                <div v-else class="py-20 text-center rounded-2xl bg-neutral-900/50 border border-neutral-800 space-y-4">
                    <Users class="w-12 h-12 text-neutral-600 mx-auto" />
                    <h3 class="font-heading font-bold text-white text-lg">لم يتم العثور على فنانين</h3>
                    <p class="text-sm text-neutral-400">يرجى تعديل معايير البحث أو اختيار تصنيف فني آخر</p>
                </div>

                <!-- Pagination -->
                <div v-if="artists.links && artists.links.length > 3" class="pt-8 flex justify-center">
                    <div class="flex items-center gap-1.5 p-1 rounded-2xl bg-neutral-900 border border-neutral-800">
                        <template v-for="(link, i) in artists.links" :key="i">
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
