<script setup lang="ts">
import { ref, watch, onMounted, onUnmounted } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import {
    Search,
    Menu,
    X,
    BookOpen,
    Film,
    Sparkles,
    Users,
    Image as ImageIcon,
    ChevronLeft,
    Clock,
    FileText,
    ArrowUpRight,
    ExternalLink,
} from 'lucide-vue-next';

const page = usePage();
const authorityName = 'الهيئة العامة للسينما والمسرح والفنون - ليبيا';

// Mobile Drawer State
const isMobileMenuOpen = ref(false);
const toggleMobileMenu = () => {
    isMobileMenuOpen.value = !isMobileMenuOpen.value;
};
const closeMobileMenu = () => {
    isMobileMenuOpen.value = false;
};

// Search Modal & Autocomplete State
const isSearchOpen = ref(false);
const searchQuery = ref('');
const searchResults = ref<{
    articles: Array<{ id: number; title: string; slug: string; category?: { name: string } }>;
    issues: Array<{ id: number; title: string; issue_number: number; slug: string }>;
    artists: Array<{ id: number; full_name: string; slug: string; art_category: string }>;
}>({
    articles: [],
    issues: [],
    artists: [],
});
const isSearching = ref(false);
let searchDebounceTimeout: any = null;

const openSearch = () => {
    isSearchOpen.value = true;
    setTimeout(() => {
        document.getElementById('instant-search-input')?.focus();
    }, 100);
};

const closeSearch = () => {
    isSearchOpen.value = false;
    searchQuery.value = '';
    searchResults.value = { articles: [], issues: [], artists: [] };
};

const performSearch = async () => {
    if (!searchQuery.value.trim() || searchQuery.value.trim().length < 2) {
        searchResults.value = { articles: [], issues: [], artists: [] };
        isSearching.value = false;
        return;
    }

    isSearching.value = true;
    try {
        const res = await fetch(`/api/search?q=${encodeURIComponent(searchQuery.value.trim())}`);
        if (res.ok) {
            const data = await res.json();
            searchResults.value = data;
        }
    } catch {
        // silent fail
    } finally {
        isSearching.value = false;
    }
};

watch(searchQuery, () => {
    clearTimeout(searchDebounceTimeout);
    searchDebounceTimeout = setTimeout(performSearch, 300);
});

// Keyboard shortcut for search (Ctrl+K or Cmd+K)
const handleKeydown = (e: KeyboardEvent) => {
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        openSearch();
    }
    if (e.key === 'Escape' && isSearchOpen.value) {
        closeSearch();
    }
};

onMounted(() => {
    window.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleKeydown);
});

// Navigation Links
const navItems = [
    { name: 'الرئيسية', href: '/', routeName: 'home' },
    { name: 'الأعداد', href: '/issues', routeName: 'issues.index' },
    { name: 'المسرح', href: '/tracks/theatre', routeName: 'tracks.theatre' },
    { name: 'السينما', href: '/tracks/cinema', routeName: 'tracks.cinema' },
    { name: 'الفنون التشكيلية والموسيقى', href: '/tracks/fine-arts-music', routeName: 'tracks.fine-arts-music' },
    { name: 'دليل الرواد والفنانين', href: '/artists', routeName: 'artists.index' },
    { name: 'المعرض الرقمي', href: '/gallery', routeName: 'gallery.index' },
];

const isCurrentUrl = (url: string) => {
    if (url === '/') {
        return page.url === '/' || page.url === '';
    }
    return page.url.startsWith(url);
};
</script>

<template>
    <div class="min-h-screen flex flex-col bg-neutral-950 text-neutral-100 font-sans selection:bg-amber-500 selection:text-neutral-950">
        <!-- Top Cultural Ribbon / National Accent -->
        <div class="w-full h-1 bg-gradient-to-r from-emerald-600 via-amber-500 to-red-700"></div>

        <!-- Header -->
        <header class="sticky top-0 z-40 bg-neutral-950/90 backdrop-blur-md border-b border-neutral-800/80 transition-all">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-20">
                    
                    <!-- Brand & Cultural Identity Touch -->
                    <div class="flex items-center gap-4">
                        <Link href="/" class="flex items-center gap-3 group">
                            <!-- Emblem Logo Graphic -->
                            <div class="relative w-12 h-12 rounded-xl bg-gradient-to-br from-amber-600 to-amber-950 p-[1.5px] shadow-lg shadow-amber-950/30 group-hover:scale-105 transition-transform duration-300">
                                <div class="w-full h-full bg-neutral-950 rounded-xl flex items-center justify-center p-2 overflow-hidden relative">
                                    <div class="absolute inset-0 bg-gradient-to-tr from-amber-500/10 to-transparent"></div>
                                    <!-- Cultural Cinema/Arts Icon -->
                                    <div class="w-7 h-7 text-amber-500 flex items-center justify-center font-bold text-xl font-heading">
                                        ف
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-col">
                                <span class="font-heading font-black text-xl sm:text-2xl tracking-tight text-white group-hover:text-amber-400 transition-colors">
                                    مجلة الفنون والسينما
                                </span>
                                <span class="text-[11px] text-neutral-400 font-medium tracking-wide hidden sm:block">
                                    الهيئة العامة للسينما والمسرح والفنون
                                </span>
                            </div>
                        </Link>
                    </div>

                    <!-- Desktop Navigation Links -->
                    <nav class="hidden xl:flex items-center gap-1 2xl:gap-2">
                        <Link
                            v-for="item in navItems"
                            :key="item.href"
                            :href="item.href"
                            class="px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200 relative"
                            :class="[
                                isCurrentUrl(item.href)
                                    ? 'text-amber-400 bg-amber-500/10 font-bold'
                                    : 'text-neutral-300 hover:text-white hover:bg-neutral-900/60'
                            ]"
                        >
                            {{ item.name }}
                            <span
                                v-if="isCurrentUrl(item.href)"
                                class="absolute bottom-0 right-3 left-3 h-0.5 bg-amber-500 rounded-full"
                            ></span>
                        </Link>
                    </nav>

                    <!-- Header Actions (Search & Mobile Toggle) -->
                    <div class="flex items-center gap-3">
                        <!-- Quick Search Button -->
                        <button
                            type="button"
                            @click="openSearch"
                            class="flex items-center gap-2.5 px-3.5 py-2 rounded-xl bg-neutral-900/80 border border-neutral-800 text-neutral-400 hover:text-white hover:border-neutral-700 transition-colors text-sm shadow-inner group"
                            title="بحث سريع (Ctrl+K)"
                        >
                            <Search class="w-4 h-4 text-neutral-400 group-hover:text-amber-400 transition-colors" />
                            <span class="hidden md:inline text-xs text-neutral-400 font-medium">بحث في الأرشيف...</span>
                            <kbd class="hidden md:inline text-[10px] bg-neutral-800 text-neutral-400 px-1.5 py-0.5 rounded border border-neutral-700 font-mono">
                                Ctrl+K
                            </kbd>
                        </button>

                        <!-- Mobile Drawer Toggle -->
                        <button
                            type="button"
                            @click="toggleMobileMenu"
                            class="xl:hidden p-2 rounded-xl text-neutral-300 hover:text-white hover:bg-neutral-900 border border-neutral-800 transition-colors"
                            aria-label="القائمة الرئيسية"
                        >
                            <Menu v-if="!isMobileMenuOpen" class="w-6 h-6" />
                            <X v-else class="w-6 h-6" />
                        </button>
                    </div>

                </div>
            </div>
        </header>

        <!-- Mobile Drawer Navigation (RTL Slide-over) -->
        <Transition
            enter-active-class="transition-opacity duration-300 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-200 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="isMobileMenuOpen"
                class="fixed inset-0 z-50 bg-black/70 backdrop-blur-sm xl:hidden"
                @click="closeMobileMenu"
            ></div>
        </Transition>

        <Transition
            enter-active-class="transition-transform duration-300 ease-out"
            enter-from-class="translate-x-full"
            enter-to-class="translate-x-0"
            leave-active-class="transition-transform duration-200 ease-in"
            leave-from-class="translate-x-0"
            leave-to-class="translate-x-full"
        >
            <aside
                v-if="isMobileMenuOpen"
                class="fixed top-0 right-0 z-50 w-80 max-w-[85vw] h-full bg-neutral-950 border-l border-neutral-800 p-6 flex flex-col justify-between shadow-2xl xl:hidden overflow-y-auto"
            >
                <div class="space-y-6">
                    <!-- Drawer Header -->
                    <div class="flex items-center justify-between pb-5 border-b border-neutral-800">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-amber-600/20 border border-amber-500/30 flex items-center justify-center font-bold text-amber-500 font-heading text-lg">
                                ف
                            </div>
                            <div class="flex flex-col">
                                <span class="font-heading font-bold text-white text-base">مجلة الفنون والسينما</span>
                                <span class="text-[10px] text-neutral-400">الهيئة العامة للسينما والمسرح</span>
                            </div>
                        </div>
                        <button
                            @click="closeMobileMenu"
                            class="p-2 rounded-lg text-neutral-400 hover:text-white hover:bg-neutral-900"
                        >
                            <X class="w-5 h-5" />
                        </button>
                    </div>

                    <!-- Mobile Navigation Links -->
                    <nav class="space-y-1.5">
                        <Link
                            v-for="item in navItems"
                            :key="item.href"
                            :href="item.href"
                            @click="closeMobileMenu"
                            class="flex items-center justify-between px-4 py-3 rounded-xl text-sm font-medium transition-colors"
                            :class="[
                                isCurrentUrl(item.href)
                                    ? 'text-amber-400 bg-amber-500/10 font-bold border border-amber-500/20'
                                    : 'text-neutral-300 hover:text-white hover:bg-neutral-900'
                            ]"
                        >
                            <span>{{ item.name }}</span>
                            <ChevronLeft class="w-4 h-4 text-neutral-500" />
                        </Link>
                    </nav>
                </div>

                <!-- Drawer Footer Cultural Note -->
                <div class="pt-6 border-t border-neutral-800 text-xs text-neutral-500 space-y-2">
                    <p class="font-semibold text-neutral-400">دولة ليبيا</p>
                    <p>منصة الثقافة والتوثيق الرقمي للفنون والسينما والمسرح.</p>
                </div>
            </aside>
        </Transition>

        <!-- Instant Autocomplete Search Modal -->
        <Transition
            enter-active-class="transition-opacity duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="isSearchOpen"
                class="fixed inset-0 z-50 bg-black/80 backdrop-blur-md flex items-start justify-center p-4 sm:p-6 md:p-20 overflow-y-auto"
                @click.self="closeSearch"
            >
                <div class="w-full max-w-2xl bg-neutral-900 border border-neutral-800 rounded-2xl shadow-2xl overflow-hidden animate-in fade-in zoom-in-95 duration-200">
                    <!-- Search Input Area -->
                    <div class="relative flex items-center px-4 py-3.5 border-b border-neutral-800 bg-neutral-950/60">
                        <Search class="w-5 h-5 text-neutral-400 ml-3 shrink-0" />
                        <input
                            id="instant-search-input"
                            v-model="searchQuery"
                            type="search"
                            dir="rtl"
                            placeholder="ابحث عن مقال، عدد، كاتب، أو فنان رائد..."
                            class="w-full bg-transparent text-white placeholder-neutral-500 text-base focus:outline-none"
                            autocomplete="off"
                        />
                        <button
                            v-if="searchQuery"
                            @click="searchQuery = ''"
                            class="text-neutral-500 hover:text-white p-1 ml-2"
                        >
                            <X class="w-4 h-4" />
                        </button>
                        <kbd class="text-[11px] bg-neutral-800 text-neutral-400 px-2 py-1 rounded border border-neutral-700 font-mono shrink-0 mr-2">
                            ESC
                        </kbd>
                    </div>

                    <!-- Search Results / Suggestions -->
                    <div class="max-h-[60vh] overflow-y-auto p-4 space-y-6">
                        <div v-if="isSearching" class="py-12 text-center text-neutral-400 text-sm flex items-center justify-center gap-3">
                            <div class="w-5 h-5 border-2 border-amber-500 border-t-transparent rounded-full animate-spin"></div>
                            <span>جاري البحث في الأرشيف الرقمي...</span>
                        </div>

                        <!-- No Results State -->
                        <div
                            v-else-if="searchQuery && !searchResults.articles.length && !searchResults.issues.length && !searchResults.artists.length"
                            class="py-12 text-center text-neutral-400 space-y-2"
                        >
                            <p class="text-base text-neutral-300">لم يتم العثور على نتائج تطابق "{{ searchQuery }}"</p>
                            <p class="text-xs text-neutral-500">جرب البحث بكلمات عامة مثل (سينما، مسرح، العدد الأول، توثيق)</p>
                        </div>

                        <!-- Initial Quick Filter Tags when empty -->
                        <div v-else-if="!searchQuery" class="space-y-3 py-4">
                            <span class="text-xs font-semibold text-neutral-500 uppercase tracking-wider block">روابط سريعة واستكشاف</span>
                            <div class="flex flex-wrap gap-2">
                                <Link
                                    v-for="item in navItems.slice(1)"
                                    :key="item.href"
                                    :href="item.href"
                                    @click="closeSearch"
                                    class="px-3 py-1.5 rounded-lg bg-neutral-800/80 hover:bg-amber-500/20 text-neutral-300 hover:text-amber-400 text-xs transition-colors"
                                >
                                    {{ item.name }}
                                </Link>
                            </div>
                        </div>

                        <!-- Results Groups -->
                        <div v-else class="space-y-6">
                            <!-- Articles Group -->
                            <div v-if="searchResults.articles.length" class="space-y-2">
                                <div class="flex items-center justify-between text-xs font-semibold text-amber-500 uppercase tracking-wider pb-1 border-b border-neutral-800">
                                    <span class="flex items-center gap-1.5">
                                        <FileText class="w-3.5 h-3.5" /> المقالات والدراسات
                                    </span>
                                    <span>{{ searchResults.articles.length }}</span>
                                </div>
                                <div class="divide-y divide-neutral-800/50">
                                    <Link
                                        v-for="art in searchResults.articles"
                                        :key="art.id"
                                        :href="`/articles/${art.slug}`"
                                        @click="closeSearch"
                                        class="p-2.5 rounded-xl hover:bg-neutral-800/60 flex items-center justify-between group transition-colors"
                                    >
                                        <div class="space-y-0.5">
                                            <p class="text-sm font-medium text-neutral-200 group-hover:text-amber-400 transition-colors">
                                                {{ art.title }}
                                            </p>
                                            <p v-if="art.category" class="text-xs text-neutral-500">
                                                {{ art.category.name }}
                                            </p>
                                        </div>
                                        <ChevronLeft class="w-4 h-4 text-neutral-600 group-hover:text-amber-400 transition-colors" />
                                    </Link>
                                </div>
                            </div>

                            <!-- Issues Group -->
                            <div v-if="searchResults.issues.length" class="space-y-2">
                                <div class="flex items-center justify-between text-xs font-semibold text-amber-500 uppercase tracking-wider pb-1 border-b border-neutral-800">
                                    <span class="flex items-center gap-1.5">
                                        <BookOpen class="w-3.5 h-3.5" /> أعداد المجلة
                                    </span>
                                    <span>{{ searchResults.issues.length }}</span>
                                </div>
                                <div class="divide-y divide-neutral-800/50">
                                    <Link
                                        v-for="iss in searchResults.issues"
                                        :key="iss.id"
                                        :href="`/issues/${iss.slug}`"
                                        @click="closeSearch"
                                        class="p-2.5 rounded-xl hover:bg-neutral-800/60 flex items-center justify-between group transition-colors"
                                    >
                                        <div>
                                            <p class="text-sm font-medium text-neutral-200 group-hover:text-amber-400 transition-colors">
                                                العدد #{{ iss.issue_number }}: {{ iss.title }}
                                            </p>
                                        </div>
                                        <ChevronLeft class="w-4 h-4 text-neutral-600 group-hover:text-amber-400 transition-colors" />
                                    </Link>
                                </div>
                            </div>

                            <!-- Artists Group -->
                            <div v-if="searchResults.artists.length" class="space-y-2">
                                <div class="flex items-center justify-between text-xs font-semibold text-amber-500 uppercase tracking-wider pb-1 border-b border-neutral-800">
                                    <span class="flex items-center gap-1.5">
                                        <Users class="w-3.5 h-3.5" /> الرواد والفنانون
                                    </span>
                                    <span>{{ searchResults.artists.length }}</span>
                                </div>
                                <div class="divide-y divide-neutral-800/50">
                                    <Link
                                        v-for="artist in searchResults.artists"
                                        :key="artist.id"
                                        :href="`/artists/${artist.slug}`"
                                        @click="closeSearch"
                                        class="p-2.5 rounded-xl hover:bg-neutral-800/60 flex items-center justify-between group transition-colors"
                                    >
                                        <div>
                                            <p class="text-sm font-medium text-neutral-200 group-hover:text-amber-400 transition-colors">
                                                {{ artist.full_name }}
                                            </p>
                                        </div>
                                        <ChevronLeft class="w-4 h-4 text-neutral-600 group-hover:text-amber-400 transition-colors" />
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- Main Content Slot -->
        <main class="flex-1">
            <slot />
        </main>

        <!-- Cultural Editorial Footer -->
        <footer class="bg-neutral-950 border-t border-neutral-800 text-neutral-400 pt-16 pb-12 mt-20 relative overflow-hidden">
            <!-- Subtle Background Glow -->
            <div class="absolute bottom-0 right-1/4 w-96 h-96 bg-amber-600/5 rounded-full blur-3xl pointer-events-none"></div>

            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-10 pb-12 border-b border-neutral-800/80">
                    
                    <!-- Col 1: Identity & Description (2 cols wide on LG) -->
                    <div class="lg:col-span-2 space-y-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-amber-500 to-amber-800 flex items-center justify-center font-bold text-neutral-950 font-heading text-lg">
                                ف
                            </div>
                            <div class="flex flex-col">
                                <span class="font-heading font-bold text-white text-lg">مجلة الفنون والسينما</span>
                                <span class="text-xs text-neutral-400 font-medium">الهيئة العامة للسينما والمسرح والفنون</span>
                            </div>
                        </div>
                        <p class="text-sm text-neutral-400 leading-relaxed max-w-md">
                            دورية ثقافية نقدية محكّمة توثق المشهد الفني والسينمائي والمسرحي في ليبيا، وتعنى بنشر الدراسات المتخصصة وحوارات الرواد وأعمال المبدعين.
                        </p>
                        <div class="flex items-center gap-4 text-xs text-neutral-400 pt-2">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-neutral-900 border border-neutral-800">
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                الإصدار الرقمي الرسمي
                            </span>
                            <span>دولة ليبيا</span>
                        </div>
                    </div>

                    <!-- Col 2: Sections & Tracks -->
                    <div class="space-y-3">
                        <h4 class="text-sm font-bold text-white font-heading">المسارات الثقافية</h4>
                        <ul class="space-y-2 text-sm">
                            <li><Link href="/tracks/cinema" class="hover:text-amber-400 transition-colors">شؤون السينما والنقد</Link></li>
                            <li><Link href="/tracks/theatre" class="hover:text-amber-400 transition-colors">المسرح وفنون الأداء</Link></li>
                            <li><Link href="/tracks/fine-arts" class="hover:text-amber-400 transition-colors">الفنون التشكيلية والبصرية</Link></li>
                            <li><Link href="/tracks/music" class="hover:text-amber-400 transition-colors">الموسيقى والمألوف الليبي</Link></li>
                        </ul>
                    </div>

                    <!-- Col 3: Archives & Knowledge -->
                    <div class="space-y-3">
                        <h4 class="text-sm font-bold text-white font-heading">الأرشيف والمكتبة</h4>
                        <ul class="space-y-2 text-sm">
                            <li><Link href="/issues" class="hover:text-amber-400 transition-colors">أرشيف أعداد المجلة</Link></li>
                            <li><Link href="/artists" class="hover:text-amber-400 transition-colors">دليل الرواد والفنانين</Link></li>
                            <li><Link href="/gallery" class="hover:text-amber-400 transition-colors">المعرض الفوتوغرافي والرقمي</Link></li>
                            <li><Link href="/articles" class="hover:text-amber-400 transition-colors">الدراسات والمقالات</Link></li>
                        </ul>
                    </div>

                    <!-- Col 4: Authority Info & Social -->
                    <div class="space-y-3">
                        <h4 class="text-sm font-bold text-white font-heading">روابط رسمية</h4>
                        <ul class="space-y-2 text-sm">
                            <li>
                                <a href="https://gacta.ly" target="_blank" rel="noopener" class="inline-flex items-center gap-1 hover:text-amber-400 transition-colors">
                                    <span>موقع الهيئة العامة</span>
                                    <ArrowUpRight class="w-3.5 h-3.5 text-neutral-500" />
                                </a>
                            </li>
                            <li>
                                <a href="https://cinema.gov.ly" target="_blank" rel="noopener" class="inline-flex items-center gap-1 hover:text-amber-400 transition-colors">
                                    <span>إدارة السينما الوطنية</span>
                                    <ArrowUpRight class="w-3.5 h-3.5 text-neutral-500" />
                                </a>
                            </li>
                            <li><span class="text-neutral-500 text-xs">طرابلس - دولة ليبيا</span></li>
                        </ul>
                    </div>

                </div>

                <!-- Bottom Copyright & Legal Bar -->
                <div class="pt-8 flex flex-col sm:flex-row items-center justify-between text-xs text-neutral-500 gap-4">
                    <p>
                        جميع الحقوق محفوظة © {{ new Date().getFullYear() }} الهيئة العامة للسينما والمسرح والفنون - دولة ليبيا.
                    </p>
                    <div class="flex items-center gap-6">
                        <span>الرقم الدولي الموحد للدوريات (ISSN): 2958-812X</span>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</template>
