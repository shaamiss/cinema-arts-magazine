<script setup lang="ts">
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '../../../Layouts/AppLayout.vue';
import SeoHead from '../../../Components/SeoHead.vue';
import {
    Image as ImageIcon,
    Video,
    Music,
    FileText,
    Filter,
    Play,
    Maximize2,
    X,
    Eye,
    Download
} from 'lucide-vue-next';

interface MediaItem {
    id: number;
    title: string;
    slug: string;
    type: 'image' | 'video' | 'audio' | 'pdf';
    file_path: string;
    thumbnail_path?: string | null;
    duration_seconds?: number | null;
    size_bytes?: number | null;
    description?: string | null;
    created_at?: string | null;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface MediaPagination {
    data: MediaItem[];
    links: PaginationLink[];
    current_page: number;
    last_page: number;
    total: number;
}

interface TypeOption {
    value: string;
    label: string;
}

const props = defineProps<{
    media: MediaPagination;
    types: TypeOption[];
    currentType?: string | null;
}>();

// Lightbox Preview State
const selectedMedia = ref<MediaItem | null>(null);

const openLightbox = (item: MediaItem) => {
    selectedMedia.value = item;
};

const closeLightbox = () => {
    selectedMedia.value = null;
};

const filterByType = (type: string | null) => {
    router.get('/gallery', type ? { type } : {}, { preserveState: true, preserveScroll: true });
};

const formatDuration = (seconds?: number | null) => {
    if (!seconds) return '';
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${mins}:${secs < 10 ? '0' : ''}${secs}`;
};

const formatSize = (bytes?: number | null) => {
    if (!bytes) return '';
    const mb = bytes / (1024 * 1024);
    return `${mb.toFixed(1)} ميغابايت`;
};

const getTypeLabel = (type: string) => {
    switch (type) {
        case 'image': return 'صورة أرشيفية';
        case 'video': return 'مرئي / وثائقي';
        case 'audio': return 'تسجيل صوتي';
        case 'pdf': return 'وثيقة PDF';
        default: return type;
    }
};
</script>

<template>
    <AppLayout>
        <SeoHead
            title="المعرض الرقمي والأرشيف الوثائقي | مجلة الفنون والسينما"
            description="المعرض الرقمي الرسمي لملصقات السينما، لقطات المسرح التاريخية، والتسجيلات الموسيقية التراثية الصادرة عن الهيئة العامة للسينما والمسرح والفنون."
            type="website"
        />

        <!-- Lightbox / Preview Modal -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0 scale-95"
                enter-to-class="opacity-100 scale-100"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="opacity-100 scale-100"
                leave-to-class="opacity-0 scale-95"
            >
                <div
                    v-if="selectedMedia"
                    class="fixed inset-0 z-50 bg-black/90 backdrop-blur-md flex items-center justify-center p-4 sm:p-6"
                    @click.self="closeLightbox"
                >
                    <div class="relative max-w-4xl w-full bg-neutral-900 border border-neutral-800 rounded-3xl overflow-hidden shadow-2xl flex flex-col max-h-[90vh]">
                        <!-- Modal Close Button -->
                        <button
                            type="button"
                            @click="closeLightbox"
                            class="absolute top-4 left-4 z-10 p-2 rounded-full bg-neutral-950/80 text-white hover:bg-neutral-800 transition-colors"
                        >
                            <X class="w-5 h-5" />
                        </button>

                        <!-- Media Preview Container -->
                        <div class="bg-black flex items-center justify-center max-h-[60vh] overflow-hidden relative">
                            <!-- Image -->
                            <img
                                v-if="selectedMedia.type === 'image'"
                                :src="selectedMedia.file_path.startsWith('http') ? selectedMedia.file_path : `/storage/${selectedMedia.file_path}`"
                                :alt="selectedMedia.title"
                                class="max-h-[60vh] w-auto object-contain"
                            />

                            <!-- Video -->
                            <video
                                v-else-if="selectedMedia.type === 'video'"
                                controls
                                autoplay
                                class="w-full max-h-[60vh]"
                                :src="selectedMedia.file_path.startsWith('http') ? selectedMedia.file_path : `/storage/${selectedMedia.file_path}`"
                            ></video>

                            <!-- Audio -->
                            <div v-else-if="selectedMedia.type === 'audio'" class="py-16 px-8 w-full flex flex-col items-center gap-6">
                                <div class="w-20 h-20 rounded-full bg-amber-500/20 text-amber-500 flex items-center justify-center">
                                    <Music class="w-10 h-10" />
                                </div>
                                <audio
                                    controls
                                    class="w-full max-w-md"
                                    :src="selectedMedia.file_path.startsWith('http') ? selectedMedia.file_path : `/storage/${selectedMedia.file_path}`"
                                ></audio>
                            </div>

                            <!-- PDF Document -->
                            <div v-else class="py-16 px-8 text-center space-y-4">
                                <FileText class="w-16 h-16 text-amber-500 mx-auto" />
                                <h4 class="font-heading font-bold text-white text-lg">{{ selectedMedia.title }}</h4>
                                <a
                                    :href="selectedMedia.file_path.startsWith('http') ? selectedMedia.file_path : `/storage/${selectedMedia.file_path}`"
                                    target="_blank"
                                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-amber-500 text-neutral-950 font-heading font-bold text-sm"
                                >
                                    <Download class="w-4 h-4" />
                                    <span>فتح وتحميل الوثيقة</span>
                                </a>
                            </div>
                        </div>

                        <!-- Modal Meta -->
                        <div class="p-6 text-right space-y-2">
                            <span class="inline-block px-2.5 py-0.5 rounded text-[11px] font-heading bg-amber-500/10 text-amber-400">
                                {{ getTypeLabel(selectedMedia.type) }}
                            </span>
                            <h3 class="text-xl font-heading font-bold text-white">
                                {{ selectedMedia.title }}
                            </h3>
                            <p v-if="selectedMedia.description" class="text-sm text-neutral-400 font-light leading-relaxed">
                                {{ selectedMedia.description }}
                            </p>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <div class="py-12 bg-neutral-950 min-h-screen">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
                
                <!-- Page Title -->
                <div class="border-b border-neutral-800 pb-8 flex flex-col md:flex-row md:items-end justify-between gap-6">
                    <div class="space-y-3">
                        <div class="inline-flex items-center gap-2 text-xs font-heading text-amber-500">
                            <Link href="/" class="hover:underline">الرئيسية</Link>
                            <span>/</span>
                            <span class="text-neutral-400">المعرض الرقمي</span>
                        </div>
                        <h1 class="text-3xl sm:text-4xl font-heading font-black text-white">
                            المعرض الرقمي والأرشيف الوثائقي
                        </h1>
                        <p class="text-sm sm:text-base text-neutral-400 font-light max-w-2xl">
                            سجل بصري وسمعي يضم الملصقات واللقطات الأرشيفية المسرحية والسينمائية والتسجيلات النادرة للتراث الليبي.
                        </p>
                    </div>

                    <!-- Type Filter Tabs -->
                    <div class="flex items-center gap-2 flex-wrap">
                        <button
                            type="button"
                            @click="filterByType(null)"
                            class="px-3.5 py-1.5 rounded-xl text-xs font-heading font-semibold transition-colors"
                            :class="[
                                !currentType
                                    ? 'bg-amber-500 text-neutral-950'
                                    : 'bg-neutral-900 border border-neutral-800 text-neutral-400 hover:text-white'
                            ]"
                        >
                            كافة الوسائط ({{ media.total }})
                        </button>

                        <button
                            v-for="t in types"
                            :key="t.value"
                            type="button"
                            @click="filterByType(t.value)"
                            class="px-3.5 py-1.5 rounded-xl text-xs font-heading font-semibold transition-colors"
                            :class="[
                                currentType === t.value
                                    ? 'bg-amber-500 text-neutral-950'
                                    : 'bg-neutral-900 border border-neutral-800 text-neutral-400 hover:text-white'
                            ]"
                        >
                            {{ getTypeLabel(t.value) }}
                        </button>
                    </div>
                </div>

                <!-- Gallery Grid -->
                <div v-if="media.data.length" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                    <div
                        v-for="item in media.data"
                        :key="item.id"
                        @click="openLightbox(item)"
                        class="group cursor-pointer rounded-2xl bg-neutral-900/80 border border-neutral-800 hover:border-amber-500/40 overflow-hidden transition-all duration-300 hover:-translate-y-1 flex flex-col justify-between"
                    >
                        <div>
                            <!-- Thumbnail Cover -->
                            <div class="relative aspect-[4/3] bg-neutral-950 overflow-hidden">
                                <img
                                    v-if="item.thumbnail_path || item.type === 'image'"
                                    :src="(item.thumbnail_path || item.file_path).startsWith('http') ? (item.thumbnail_path || item.file_path) : `/storage/${item.thumbnail_path || item.file_path}`"
                                    :alt="item.title"
                                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                />
                                <div v-else class="w-full h-full flex items-center justify-center bg-neutral-900 text-neutral-600">
                                    <Video v-if="item.type === 'video'" class="w-10 h-10 text-amber-500/60" />
                                    <Music v-else-if="item.type === 'audio'" class="w-10 h-10 text-amber-500/60" />
                                    <FileText v-else class="w-10 h-10 text-amber-500/60" />
                                </div>

                                <!-- Overlay Icon for Video / Audio -->
                                <div class="absolute inset-0 bg-neutral-950/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                    <div class="w-12 h-12 rounded-full bg-amber-500 text-neutral-950 flex items-center justify-center shadow-lg transform group-hover:scale-110 transition-transform">
                                        <Play v-if="item.type === 'video' || item.type === 'audio'" class="w-5 h-5 ml-0.5 fill-current" />
                                        <Maximize2 v-else class="w-5 h-5" />
                                    </div>
                                </div>

                                <!-- Corner Type Badge -->
                                <span class="absolute top-2.5 right-2.5 px-2.5 py-0.5 rounded-md bg-neutral-950/85 backdrop-blur-xs text-[10px] font-heading font-semibold text-amber-400 border border-neutral-800">
                                    {{ getTypeLabel(item.type) }}
                                </span>
                            </div>

                            <!-- Content Details -->
                            <div class="p-4 space-y-1.5">
                                <h3 class="font-heading font-bold text-sm text-white group-hover:text-amber-400 transition-colors line-clamp-1">
                                    {{ item.title }}
                                </h3>
                                <p v-if="item.description" class="text-xs text-neutral-400 line-clamp-2 font-light">
                                    {{ item.description }}
                                </p>
                            </div>
                        </div>

                        <!-- Footer Details -->
                        <div class="px-4 pb-4 pt-1 flex items-center justify-between text-[11px] text-neutral-500">
                            <span v-if="item.duration_seconds">
                                {{ formatDuration(item.duration_seconds) }}
                            </span>
                            <span v-else-if="item.size_bytes">
                                {{ formatSize(item.size_bytes) }}
                            </span>
                            <span class="text-amber-500 font-medium group-hover:underline">
                                معاينة
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="py-20 text-center rounded-2xl bg-neutral-900/50 border border-neutral-800 space-y-4">
                    <ImageIcon class="w-12 h-12 text-neutral-600 mx-auto" />
                    <h3 class="font-heading font-bold text-white text-lg">لا توجد وسائط مسجلة</h3>
                    <p class="text-sm text-neutral-400">يرجى اختيار تصنيف آخر أو إعادة التصفية</p>
                </div>

                <!-- Pagination -->
                <div v-if="media.links && media.links.length > 3" class="pt-8 flex justify-center">
                    <div class="flex items-center gap-1.5 p-1 rounded-2xl bg-neutral-900 border border-neutral-800">
                        <template v-for="(link, i) in media.links" :key="i">
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
