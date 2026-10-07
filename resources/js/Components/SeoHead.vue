<script setup lang="ts">
import { computed } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';

interface Props {
    title?: string;
    description?: string;
    image?: string | null;
    type?: 'website' | 'article' | 'issue';
    canonical?: string;
    publishedAt?: string | null;
    authorName?: string | null;
    categoryName?: string | null;
    issueNumber?: number | null;
}

const props = withDefaults(defineProps<Props>(), {
    title: 'مجلة الفنون والسينما',
    description: 'المجلة الرسمية للهيئة العامة للسينما والمسرح والفنون - دولة ليبيا. توثيق ونقد وإبداع في السينما والمسرح والموسيقى والفنون التشكيلية.',
    image: null,
    type: 'website',
    canonical: '',
    publishedAt: null,
    authorName: null,
    categoryName: null,
    issueNumber: null,
});

const page = usePage();
const siteName = computed(() => (page.props.appName as string) || 'مجلة الفنون والسينما');
const authorityName = computed(() => (page.props.authorityName as string) || 'الهيئة العامة للسينما والمسرح والفنون - دولة ليبيا');

const computedTitle = computed(() => {
    if (!props.title || props.title === siteName.value) {
        return `${siteName.value} | ${authorityName.value}`;
    }
    return `${props.title} - ${siteName.value}`;
});

const currentUrl = computed(() => {
    if (props.canonical) return props.canonical;
    if (typeof window !== 'undefined') {
        return window.location.href;
    }
    return '';
});

const defaultOgImage = 'https://images.unsplash.com/photo-1489599849927-2ee91cede3ba?q=80&w=1200&auto=format&fit=crop';
const ogImage = computed(() => props.image || defaultOgImage);

// Schema.org JSON-LD structured data
const jsonLd = computed(() => {
    if (props.type === 'article') {
        return {
            '@context': 'https://schema.org',
            '@type': 'Article',
            'headline': props.title,
            'description': props.description,
            'image': [ogImage.value],
            'datePublished': props.publishedAt || new Date().toISOString(),
            'author': {
                '@type': 'Person',
                'name': props.authorName || authorityName.value,
            },
            'publisher': {
                '@type': 'Organization',
                'name': authorityName.value,
                'logo': {
                    '@type': 'ImageObject',
                    'url': defaultOgImage,
                },
            },
            'articleSection': props.categoryName || 'الفنون والسينما',
            'mainEntityOfPage': {
                '@type': 'WebPage',
                '@id': currentUrl.value,
            },
        };
    }

    if (props.type === 'issue') {
        return {
            '@context': 'https://schema.org',
            '@type': 'PublicationIssue',
            'name': props.title,
            'issueNumber': props.issueNumber || 1,
            'description': props.description,
            'image': ogImage.value,
            'datePublished': props.publishedAt || new Date().toISOString(),
            'isPartOf': {
                '@type': 'Periodical',
                'name': siteName.value,
                'publisher': {
                    '@type': 'Organization',
                    'name': authorityName.value,
                },
            },
        };
    }

    return {
        '@context': 'https://schema.org',
        '@type': 'WebSite',
        'name': siteName.value,
        'alternateName': authorityName.value,
        'url': currentUrl.value,
        'description': props.description,
        'inLanguage': 'ar-LY',
    };
});
</script>

<template>
    <Head :title="title">
        <meta name="description" :content="description" />
        <link v-if="canonical" rel="canonical" :href="canonical" />

        <!-- OpenGraph Metadata -->
        <meta property="og:site_name" :content="siteName" />
        <meta property="og:title" :content="title" />
        <meta property="og:description" :content="description" />
        <meta property="og:image" :content="ogImage" />
        <meta property="og:type" :content="type === 'article' ? 'article' : 'website'" />
        <meta property="og:url" :content="currentUrl" />
        <meta property="og:locale" :content="'ar_LY'" />

        <!-- Twitter Card Metadata -->
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" :content="title" />
        <meta name="twitter:description" :content="description" />
        <meta name="twitter:image" :content="ogImage" />

        <!-- Schema.org JSON-LD -->
        <component
            :is="'script'"
            type="application/ld+json"
            v-html="JSON.stringify(jsonLd)"
        />
    </Head>
</template>
