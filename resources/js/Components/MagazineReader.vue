<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { useFullscreen } from '../Composables/useFullscreen';
import { useSwipe } from '../Composables/useSwipe';

export interface IssueData {
    id: number;
    title: string;
    issue_number: number;
    slug: string;
    pages_count: number;
    pdf_url?: string | null;
}

const props = withDefaults(
    defineProps<{
        issue: IssueData;
        pages: string[];
        thumbnails?: string[];
        initialPage?: number;
    }>(),
    {
        thumbnails: () => [],
        initialPage: 1,
    }
);

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'page-change', page: number): void;
}>();

// Fullscreen composable
const readerContainerRef = ref<HTMLElement | null>(null);
const { isFullscreen, toggleFullscreen } = useFullscreen();

// View Mode: Dual-page (Desktop >= 1024px) vs Single-page (Mobile/Tablet < 1024px)
const isDesktop = ref(window.innerWidth >= 1024);
const handleResize = () => {
    isDesktop.value = window.innerWidth >= 1024;
};

// Current Active Page (1-indexed)
const currentPage = ref(Math.max(1, Math.min(props.initialPage, props.pages.length || 1)));
const totalPages = computed(() => Math.max(props.pages.length, props.issue.pages_count || 1));

// Zoom Controls (1x, 1.25x, 1.5x, 2x)
const zoomLevel = ref(1);
const zoomLevels = [0.85, 1, 1.25, 1.5, 2];
const canZoomIn = computed(() => zoomLevel.value < 2);
const canZoomOut = computed(() => zoomLevel.value > 0.85);

const zoomIn = () => {
    const next = zoomLevels.find((z) => z > zoomLevel.value);
    if (next) zoomLevel.value = next;
};
const zoomOut = () => {
    const prev = [...zoomLevels].reverse().find((z) => z < zoomLevel.value);
    if (prev) zoomLevel.value = prev;
};
const resetZoom = () => {
    zoomLevel.value = 1;
};

// Spread Calculation for Dual-Page (RTL Layout)
// In Arabic RTL:
// Page 1 is the front cover (alone on the right).
// Page 2 is on the Left, Page 3 is on the Right.
// Page 4 is on the Left, Page 5 is on the Right.
const visibleSpread = computed(() => {
    if (!isDesktop.value) {
        return {
            leftPage: null,
            rightPage: currentPage.value,
            isSingle: true,
        };
    }

    if (currentPage.value === 1) {
        return {
            leftPage: null,
            rightPage: 1,
            isSingle: true,
        };
    }

    // In Arabic reading: even page is on Left, odd page is on Right
    const right = currentPage.value % 2 === 1 ? currentPage.value : currentPage.value + 1;
    const left = right - 1;

    return {
        leftPage: left <= totalPages.value ? left : null,
        rightPage: right <= totalPages.value ? right : null,
        isSingle: false,
    };
});

// Flip Animation State
const isFlipping = ref(false);
const flipDirection = ref<'next' | 'prev' | null>(null);

// Navigation (RTL Context: Next advances forward, Prev retreats backward)
const canGoNext = computed(() => currentPage.value < totalPages.value);
const canGoPrev = computed(() => currentPage.value > 1);

const nextPage = () => {
    if (!canGoNext.value || isFlipping.value) return;

    flipDirection.value = 'next';
    isFlipping.value = true;

    setTimeout(() => {
        if (isDesktop.value) {
            if (currentPage.value === 1) {
                currentPage.value = 2;
            } else {
                currentPage.value = Math.min(totalPages.value, currentPage.value + 2);
            }
        } else {
            currentPage.value = Math.min(totalPages.value, currentPage.value + 1);
        }
        isFlipping.value = false;
        flipDirection.value = null;
    }, 280);
};

const prevPage = () => {
    if (!canGoPrev.value || isFlipping.value) return;

    flipDirection.value = 'prev';
    isFlipping.value = true;

    setTimeout(() => {
        if (isDesktop.value) {
            if (currentPage.value <= 3) {
                currentPage.value = 1;
            } else {
                currentPage.value = Math.max(1, currentPage.value - 2);
            }
        } else {
            currentPage.value = Math.max(1, currentPage.value - 1);
        }
        isFlipping.value = false;
        flipDirection.value = null;
    }, 280);
};

const goToPage = (pageNumber: number) => {
    const target = Math.max(1, Math.min(pageNumber, totalPages.value));
    if (target === currentPage.value) return;
    currentPage.value = target;
};

// Touch / Swipe Navigation for Mobile & Tablet (In RTL: swipe left = next, swipe right = prev)
useSwipe(readerContainerRef, {
    threshold: 60,
    onSwipeLeft: () => {
        nextPage();
    },
    onSwipeRight: () => {
        prevPage();
    },
});

// Keyboard Navigation (RTL: Left Arrow advances forward, Right Arrow retreats)
const handleKeydown = (e: KeyboardEvent) => {
    if (e.key === 'ArrowLeft') {
        e.preventDefault();
        nextPage();
    } else if (e.key === 'ArrowRight') {
        e.preventDefault();
        prevPage();
    } else if (e.key === 'Escape' && isFullscreen.value) {
        toggleFullscreen(readerContainerRef.value || undefined);
    }
};

// Thumbnail Drawer Toggle
const showThumbnails = ref(false);

// Lazy Buffer Calculation: Keep visible pages + 2 buffer around current view
const isPageInBuffer = (pageNumber: number) => {
    const range = isDesktop.value ? 4 : 2;
    return Math.abs(pageNumber - currentPage.value) <= range;
};

// Track loaded state of high-res images
const loadedPages = ref<Record<number, boolean>>({});
const onImageLoad = (pageNumber: number) => {
    loadedPages.value[pageNumber] = true;
};

// Watch page change to emit event
watch(currentPage, (val) => {
    emit('page-change', val);
});

onMounted(() => {
    window.addEventListener('resize', handleResize);
    window.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
    window.removeEventListener('resize', handleResize);
    window.removeEventListener('keydown', handleKeydown);
});
</script>

<template>
    <div
        ref="readerContainerRef"
        dir="rtl"
        class="relative flex flex-col w-full h-screen overflow-hidden text-neutral-100 bg-neutral-950 select-none font-sans"
    >
        <!-- 1. Top Controls Bar -->
        <header
            class="z-30 flex items-center justify-between px-4 py-3 border-b bg-neutral-900/90 backdrop-blur-md border-neutral-800/80 shadow-md transition-all duration-200"
        >
            <!-- Right: Return Button & Issue Info -->
            <div class="flex items-center gap-3">
                <button
                    type="button"
                    title="إغلاق القارئ"
                    class="p-2 rounded-lg text-neutral-400 hover:text-white hover:bg-neutral-800 transition-colors focus:outline-none focus:ring-2 focus:ring-amber-600"
                    @click="emit('close')"
                >
                    <svg class="w-5 h-5 rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                    </svg>
                </button>

                <div class="flex flex-col">
                    <span class="text-xs font-semibold tracking-wider uppercase text-amber-500">
                        الهيئة العامة للسينما والمسرح والفنون
                    </span>
                    <h1 class="text-sm font-bold text-neutral-100 truncate max-w-[220px] sm:max-w-md md:max-w-lg">
                        العدد #{{ issue.issue_number }}: {{ issue.title }}
                    </h1>
                </div>
            </div>

            <!-- Left: Zoom & Fullscreen -->
            <div class="flex items-center gap-1 sm:gap-2">
                <!-- Zoom Controls -->
                <div class="hidden sm:flex items-center bg-neutral-800/80 rounded-lg p-0.5 border border-neutral-700/60">
                    <button
                        type="button"
                        :disabled="!canZoomOut"
                        title="تصغير"
                        class="p-1.5 rounded text-neutral-300 hover:text-white hover:bg-neutral-700 disabled:opacity-30 disabled:hover:bg-transparent transition"
                        @click="zoomOut"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
                        </svg>
                    </button>

                    <button
                        type="button"
                        title="إعادة ضبط الحجم"
                        class="px-2 py-1 text-xs font-medium text-neutral-300 hover:text-white"
                        @click="resetZoom"
                    >
                        {{ Math.round(zoomLevel * 100) }}%
                    </button>

                    <button
                        type="button"
                        :disabled="!canZoomIn"
                        title="تكبير"
                        class="p-1.5 rounded text-neutral-300 hover:text-white hover:bg-neutral-700 disabled:opacity-30 disabled:hover:bg-transparent transition"
                        @click="zoomIn"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                    </button>
                </div>

                <!-- PDF Download Button -->
                <a
                    v-if="issue.pdf_url"
                    :href="issue.pdf_url"
                    target="_blank"
                    download
                    title="تحميل النسخة الرقمية الأصلية (PDF)"
                    class="p-2 rounded-lg text-neutral-300 hover:text-white hover:bg-neutral-800 transition focus:outline-none"
                >
                    <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                </a>

                <!-- Fullscreen Toggle -->
                <button
                    type="button"
                    :title="isFullscreen ? 'إنهاء وضع ملء الشاشة' : 'ملء الشاشة'"
                    class="p-2 rounded-lg text-neutral-300 hover:text-white hover:bg-neutral-800 transition focus:outline-none"
                    @click="toggleFullscreen(readerContainerRef || undefined)"
                >
                    <svg v-if="!isFullscreen" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
                    </svg>
                    <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </header>

        <!-- 2. Main Reader Canvas / Spread Area -->
        <main
            class="relative flex-1 flex items-center justify-center p-2 sm:p-6 overflow-hidden bg-radial from-neutral-900 to-neutral-950"
        >
            <!-- Navigation Arrow: Next (On Right in standard screen, but advances Left in Arabic) -->
            <button
                type="button"
                :disabled="!canGoPrev"
                title="الصفحة السابقة (السهم الأيمن)"
                class="absolute right-2 sm:right-6 z-20 p-3 rounded-full bg-neutral-900/80 hover:bg-amber-600 text-neutral-300 hover:text-white shadow-xl backdrop-blur-sm border border-neutral-800 disabled:opacity-10 disabled:pointer-events-none transition-all duration-200"
                @click="prevPage"
            >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                </svg>
            </button>

            <!-- Spread Container with 3D Perspective -->
            <div
                class="relative transition-transform duration-200 ease-out flex items-center justify-center max-w-full max-h-full"
                :style="{ transform: `scale(${zoomLevel})` }"
            >
                <!-- Desktop 2-Page Spread View -->
                <div
                    v-if="isDesktop"
                    class="relative flex items-center shadow-2xl shadow-black/90 rounded-md overflow-hidden bg-neutral-900 border border-neutral-800/80 transition-all duration-300"
                    :class="{ 'opacity-90 scale-[0.99]': isFlipping }"
                >
                    <!-- Left Page (Even number in RTL spread) -->
                    <div
                        v-if="!visibleSpread.isSingle"
                        class="relative w-[340px] md:w-[440px] lg:w-[490px] xl:w-[540px] aspect-[1/1.414] bg-neutral-900 flex items-center justify-center border-l border-neutral-800"
                    >
                        <template v-if="visibleSpread.leftPage">
                            <!-- Placeholder Skeleton -->
                            <div
                                v-if="!loadedPages[visibleSpread.leftPage]"
                                class="absolute inset-0 flex flex-col items-center justify-center bg-neutral-900 animate-pulse text-neutral-600"
                            >
                                <svg class="w-10 h-10 mb-2 animate-spin text-amber-600/50" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                </svg>
                                <span class="text-xs">جاري تحميل صفحة {{ visibleSpread.leftPage }}...</span>
                            </div>

                            <!-- Page Image -->
                            <img
                                v-if="isPageInBuffer(visibleSpread.leftPage)"
                                :src="pages[visibleSpread.leftPage - 1]"
                                :alt="`صفحة ${visibleSpread.leftPage}`"
                                class="w-full h-full object-contain pointer-events-none transition-opacity duration-300"
                                :class="{ 'opacity-0': !loadedPages[visibleSpread.leftPage], 'opacity-100': loadedPages[visibleSpread.leftPage] }"
                                @load="onImageLoad(visibleSpread.leftPage)"
                            />

                            <!-- Subtle Page Spine Gradient Shading -->
                            <div class="absolute inset-y-0 right-0 w-8 pointer-events-none bg-gradient-to-l from-black/40 to-transparent"></div>
                        </template>

                        <!-- Blank filler if page does not exist -->
                        <div v-else class="w-full h-full bg-neutral-950 flex items-center justify-center text-neutral-700 text-xs">
                            نهاية العدد
                        </div>
                    </div>

                    <!-- Right Page (Odd number / Front Cover in RTL spread) -->
                    <div
                        class="relative w-[340px] md:w-[440px] lg:w-[490px] xl:w-[540px] aspect-[1/1.414] bg-neutral-900 flex items-center justify-center"
                    >
                        <template v-if="visibleSpread.rightPage">
                            <!-- Placeholder Skeleton -->
                            <div
                                v-if="!loadedPages[visibleSpread.rightPage]"
                                class="absolute inset-0 flex flex-col items-center justify-center bg-neutral-900 animate-pulse text-neutral-600"
                            >
                                <svg class="w-10 h-10 mb-2 animate-spin text-amber-600/50" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                </svg>
                                <span class="text-xs">جاري تحميل صفحة {{ visibleSpread.rightPage }}...</span>
                            </div>

                            <!-- Page Image -->
                            <img
                                v-if="isPageInBuffer(visibleSpread.rightPage)"
                                :src="pages[visibleSpread.rightPage - 1]"
                                :alt="`صفحة ${visibleSpread.rightPage}`"
                                class="w-full h-full object-contain pointer-events-none transition-opacity duration-300"
                                :class="{ 'opacity-0': !loadedPages[visibleSpread.rightPage], 'opacity-100': loadedPages[visibleSpread.rightPage] }"
                                @load="onImageLoad(visibleSpread.rightPage)"
                            />

                            <!-- Subtle Page Spine Gradient Shading (left side of right page) -->
                            <div
                                v-if="!visibleSpread.isSingle"
                                class="absolute inset-y-0 left-0 w-8 pointer-events-none bg-gradient-to-r from-black/40 to-transparent"
                            ></div>
                        </template>
                    </div>

                    <!-- Book Center Crease Shadow -->
                    <div
                        v-if="!visibleSpread.isSingle"
                        class="absolute inset-y-0 left-1/2 -translate-x-1/2 w-4 pointer-events-none bg-gradient-to-r from-black/30 via-black/50 to-black/30 shadow-inner"
                    ></div>
                </div>

                <!-- Mobile & Tablet Single-Page View -->
                <div
                    v-else
                    class="relative w-[88vw] max-w-[480px] aspect-[1/1.414] shadow-2xl shadow-black rounded-lg overflow-hidden bg-neutral-900 border border-neutral-800"
                >
                    <!-- Skeleton -->
                    <div
                        v-if="!loadedPages[currentPage]"
                        class="absolute inset-0 flex flex-col items-center justify-center bg-neutral-900 animate-pulse text-neutral-600"
                    >
                        <svg class="w-10 h-10 mb-2 animate-spin text-amber-600/50" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        <span class="text-xs">جاري تحميل صفحة {{ currentPage }}...</span>
                    </div>

                    <!-- Single Page Image -->
                    <img
                        :src="pages[currentPage - 1]"
                        :alt="`صفحة ${currentPage}`"
                        class="w-full h-full object-contain pointer-events-none transition-opacity duration-300"
                        :class="{ 'opacity-0': !loadedPages[currentPage], 'opacity-100': loadedPages[currentPage] }"
                        @load="onImageLoad(currentPage)"
                    />
                </div>
            </div>

            <!-- Navigation Arrow: Prev (On Left in standard screen, advances forward in Arabic) -->
            <button
                type="button"
                :disabled="!canGoNext"
                title="الصفحة التالية (السهم الأيسر)"
                class="absolute left-2 sm:left-6 z-20 p-3 rounded-full bg-neutral-900/80 hover:bg-amber-600 text-neutral-300 hover:text-white shadow-xl backdrop-blur-sm border border-neutral-800 disabled:opacity-10 disabled:pointer-events-none transition-all duration-200"
                @click="nextPage"
            >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" />
                </svg>
            </button>
        </main>

        <!-- 3. Bottom Controls Bar & Scrubber -->
        <footer
            class="z-30 flex flex-col bg-neutral-900/95 backdrop-blur-md border-t border-neutral-800 shadow-xl transition-all duration-200"
        >
            <!-- Thumbnail Scrubber Drawer -->
            <div
                v-if="showThumbnails"
                class="flex items-center gap-3 px-4 py-3 overflow-x-auto border-b border-neutral-800/80 scrollbar-thin scrollbar-thumb-neutral-700 bg-neutral-950/80"
            >
                <button
                    v-for="pageNum in totalPages"
                    :key="pageNum"
                    type="button"
                    class="relative flex-shrink-0 w-16 sm:w-20 aspect-[1/1.414] rounded overflow-hidden border-2 transition-all duration-150"
                    :class="pageNum === currentPage ? 'border-amber-500 scale-105 shadow-md shadow-amber-500/20' : 'border-neutral-700 hover:border-neutral-500 opacity-60 hover:opacity-100'"
                    @click="goToPage(pageNum)"
                >
                    <img
                        :src="thumbnails[pageNum - 1] || pages[pageNum - 1]"
                        :alt="`مصغرة ${pageNum}`"
                        class="w-full h-full object-cover"
                        loading="lazy"
                    />
                    <span
                        class="absolute bottom-0 inset-x-0 py-0.5 text-[10px] font-bold text-center bg-black/75 text-neutral-200"
                    >
                        {{ pageNum }}
                    </span>
                </button>
            </div>

            <!-- Bottom Navigation Bar -->
            <div class="flex items-center justify-between px-4 py-2 sm:py-3 text-xs sm:text-sm">
                <!-- Right: Toggle Thumbnails -->
                <button
                    type="button"
                    title="استعراض المصغرات"
                    class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-neutral-300 hover:text-white hover:bg-neutral-800 transition"
                    :class="{ 'bg-neutral-800 text-amber-400': showThumbnails }"
                    @click="showThumbnails = !showThumbnails"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                    </svg>
                    <span class="hidden sm:inline">المصغرات</span>
                </button>

                <!-- Center: Page Slider & Indicator -->
                <div class="flex items-center gap-3">
                    <span class="text-xs text-neutral-400 font-medium">
                        صفحة <strong class="text-amber-400">{{ currentPage }}</strong> من {{ totalPages }}
                    </span>

                    <input
                        type="range"
                        min="1"
                        :max="totalPages"
                        :value="currentPage"
                        class="w-24 sm:w-44 h-1.5 bg-neutral-700 rounded-lg appearance-none cursor-pointer accent-amber-500"
                        @input="goToPage(Number(($event.target as HTMLInputElement).value))"
                    />
                </div>

                <!-- Left: Quick Flip Buttons -->
                <div class="flex items-center gap-1 sm:gap-2">
                    <button
                        type="button"
                        :disabled="!canGoPrev"
                        class="px-2.5 py-1 rounded bg-neutral-800 hover:bg-neutral-700 disabled:opacity-30 disabled:pointer-events-none transition text-xs font-semibold"
                        @click="prevPage"
                    >
                        السابق
                    </button>
                    <button
                        type="button"
                        :disabled="!canGoNext"
                        class="px-2.5 py-1 rounded bg-amber-600 hover:bg-amber-500 text-white disabled:opacity-30 disabled:pointer-events-none transition text-xs font-semibold shadow-sm"
                        @click="nextPage"
                    >
                        التالي
                    </button>
                </div>
            </div>
        </footer>
    </div>
</template>

<style scoped>
/* Custom styling for realistic book crease and smooth transitions */
.bg-radial {
    background: radial-gradient(circle at center, rgb(30, 30, 35) 0%, rgb(10, 10, 12) 100%);
}
</style>
