<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    categories: {
        type: Array,
        default: () => [],
    },

    bestSellers: {
        type: Array,
        default: () => [],
    },

    topTrending: {
        type: Array,
        default: () => [],
    },

    banners: {
        type: Array,
        default: () => [],
    },
});

const currentSlide = ref(0);

const bannerImage = (path) => (path.startsWith('images/') ? '/' : '/storage/') + path;

const slides = props.banners.map(b => ({
    title: b.title,
    subtitle: b.subtitle,
    button: b.button_text,
    link: b.button_link || route('search'),
    image: bannerImage(b.image_path),
    titleFont: b.title_font || 'Inter',
    subtitleFont: b.subtitle_font || 'Inter',
    buttonFont: b.button_font || 'Inter',
    titleColor: b.title_color || '#101114',
    subtitleColor: b.subtitle_color || '#666762',
    buttonTextColor: b.button_text_color || '#FFFFFF',
    buttonBackground: b.button_background || '#101114',
    buttonGradientColor: b.button_gradient_color || '#33D6C8',
    buttonStyle: b.button_style || 'fill',
}));

const buttonStyleFor = (slide) => {
    if (slide.buttonStyle === 'outline') {
        return {
            backgroundColor: 'transparent',
            color: slide.buttonBackground,
            border: `1.5px solid ${slide.buttonBackground}`,
        };
    }

    if (slide.buttonStyle === 'gradient') {
        return {
            backgroundImage: `linear-gradient(90deg, ${slide.buttonBackground}, ${slide.buttonGradientColor})`,
            color: slide.buttonTextColor,
            border: 'none',
        };
    }

    return {
        backgroundColor: slide.buttonBackground,
        color: slide.buttonTextColor,
        border: 'none',
    };
};

let slideTimer = null;

const nextSlide = () => {
    currentSlide.value =
        (currentSlide.value + 1) % slides.length;
};

const previousSlide = () => {
    currentSlide.value =
        (currentSlide.value - 1 + slides.length) % slides.length;
};

const goToSlide = (index) => {
    currentSlide.value = index;
};

const startAutoSlide = () => {
    stopAutoSlide();

    slideTimer = setInterval(() => {
        nextSlide();
    }, 5000);
};

const stopAutoSlide = () => {
    if (slideTimer) {
        clearInterval(slideTimer);
        slideTimer = null;
    }
};

onMounted(() => {
    startAutoSlide();
});

onBeforeUnmount(() => {
    stopAutoSlide();
});

const visibleCategories = computed(() => {
    const order = [
        'T-Shirts',
        'Long Sleeves',
        'Pants',
        'Jacket',
        'Shoes',
    ];

    const list = [];
    order.forEach((catName) => {
        const found = props.categories?.find(
            c => c.name?.toLowerCase() === catName.toLowerCase() || c.slug?.toLowerCase() === catName.toLowerCase()
        );
        if (found) {
            list.push(found);
        } else if (catName === 'Shoes') {
            list.push({
                id: 'shoes',
                name: 'Shoes',
                slug: 'shoes',
            });
        }
    });

    return list;
});

const productImage = (product) => {
    if (!product?.images?.[0]?.path) {
        return null;
    }

    const path = product.images[0].path;

    return path.startsWith('images/')
        ? `/${path}`
        : `/storage/${path}`;
};

const ratingColorClass = (rating) => {
    const r = Number(rating);
    if (!r) return 'text-gray-400';
    if (r < 2.5) return 'text-gray-400';
    if (r < 4) return 'text-green-500';
    return 'text-yellow-500';
};
</script>

<template>
    <GuestLayout>

        <Head title="Zypher — Buy, sell, and move fast." />

        <section class="max-w-7xl mx-auto px-4 sm:px-6 py-10">
            <!-- ================================================= -->
            <!-- HERO CAROUSEL -->
            <!-- ================================================= -->

                        <div v-if="slides.length > 0" class="relative rounded-2xl overflow-hidden mb-9 bg-[#EEEEEC] border border-[#E4E4E0]" @mouseenter="stopAutoSlide" @mouseleave="startAutoSlide">
                <div class="relative h-[190px] sm:h-[200px] lg:h-[210px]">
                    <div v-for="(slide, index) in slides" :key="index" class="absolute inset-0 transition-all duration-500 ease-out" :class="[ currentSlide === index ? 'opacity-100 translate-x-0 z-10' : 'opacity-0 translate-x-4 z-0 pointer-events-none']">

                        <!-- BANNER IMAGE PLACEHOLDER -->
                        <div class="absolute inset-0 bg-[#EEEEEC]">
                            <!--
                                PLACE YOUR BANNER IMAGE HERE LATER

                                Example:

                                <img
                                    src="/images/banner-1.jpg"
                                    class="absolute inset-0 w-full h-full object-cover"
                                />
                            -->
                            <img
                                :src="slide.image"
                                :alt="slide.title"
                                class="absolute inset-0 w-full h-full object-cover"
                            />
                        </div>

                        <!-- OPTIONAL OVERLAY -->
                        <div
                            class="absolute inset-0 bg-gradient-to-r from-white/10 via-transparent to-transparent"
                        ></div>

                        <!-- BANNER CONTENT -->
                        <div class="relative z-20 h-full flex items-center">

                            <div class="px-7 sm:px-9 lg:px-10 max-w-xl">

                                <!-- LABEL -->
                                <p
                                    class="text-[9px] sm:text-[10px] uppercase tracking-[0.22em] font-semibold text-[#777873] mb-2"
                                >
                                    Zypher Marketplace
                                </p>

                                <!-- TITLE -->
                                <h1
                                    class="text-2xl sm:text-3xl lg:text-4xl font-bold tracking-tight leading-[1.05] mb-2"
                                    :style="{ fontFamily: slide.titleFont, color: slide.titleColor }"
                                >
                                    {{ slide.title }}
                                </h1>

                                <!-- SUBTITLE -->
                                <p
                                    class="text-xs sm:text-sm max-w-md mb-3"
                                    :style="{ fontFamily: slide.subtitleFont, color: slide.subtitleColor }"
                                >
                                    {{ slide.subtitle }}
                                </p>

                                <!-- BUTTON -->
                                <Link
                                    :href="slide.link"
                                    class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg text-xs font-medium transition-opacity hover:opacity-90"
                                    :style="{ ...buttonStyleFor(slide), fontFamily: slide.buttonFont }"
                                >
                                    {{ slide.button }}

                                    <svg
                                        class="w-3.5 h-3.5"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                    >
                                        <path
                                            d="M5 12h14"
                                            stroke-linecap="round"
                                        />

                                        <path
                                            d="m13 6 6 6-6 6"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                        />
                                    </svg>
                                </Link>

                            </div>
                        </div>

                    </div>

                    <!-- PREVIOUS -->
                    <button
                        type="button"
                        @click="previousSlide"
                        class="absolute left-3 top-1/2 -translate-y-1/2 z-30 w-8 h-8 rounded-full bg-white/90 border border-[#E4E4E0] flex items-center justify-center hover:bg-white transition shadow-sm"
                        aria-label="Previous slide"
                    >
                        <svg
                            class="w-4 h-4 text-[#101114]"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path
                                d="m15 18-6-6 6-6"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                        </svg>
                    </button>

                    <!-- NEXT -->
                    <button
                        type="button"
                        @click="nextSlide"
                        class="absolute right-3 top-1/2 -translate-y-1/2 z-30 w-8 h-8 rounded-full bg-white/90 border border-[#E4E4E0] flex items-center justify-center hover:bg-white transition shadow-sm"
                        aria-label="Next slide"
                    >
                        <svg
                            class="w-4 h-4 text-[#101114]"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path
                                d="m9 18 6-6-6-6"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                        </svg>
                    </button>

                    <!-- DOTS -->
                    <div
                        class="absolute bottom-3 left-1/2 -translate-x-1/2 z-30 flex items-center gap-1.5"
                    >
                        <button
                            v-for="(_, index) in slides"
                            :key="index"
                            type="button"
                            @click="goToSlide(index)"
                            class="transition-all duration-300 rounded-full"
                            :class="
                                currentSlide === index
                                    ? 'w-5 h-1.5 bg-[#101114]'
                                    : 'w-1.5 h-1.5 bg-[#A8A9A5] hover:bg-[#70716E]'
                            "
                            :aria-label="`Go to slide ${index + 1}`"
                        ></button>
                    </div>

                </div>
            </div>

            <!-- ================================================= -->
            <!-- CATEGORIES -->
            <!-- ================================================= -->

            <div class="flex items-center gap-2.5 mb-10 overflow-x-auto pb-2 scrollbar-none">
                <Link
                    :href="route('search')"
                    class="shrink-0 px-4 py-2 rounded-full text-xs font-semibold border transition-all duration-200 bg-[#101114] text-white border-[#101114] shadow-xs"
                >
                    All Categories
                </Link>

                <Link
                    v-for="category in visibleCategories"
                    :key="category.id"
                    :href="route('search') + '?category_id=' + category.id"
                    class="shrink-0 px-4 py-2 rounded-full text-xs font-medium border border-[#E4E4E0] bg-white text-[#5F5E5A] hover:border-[#101114] hover:text-[#101114] transition-all duration-200 shadow-xs"
                >
                    {{ category.name }}
                </Link>
            </div>

            <!-- ================================================= -->
            <!-- TOP TRENDING PRODUCTS (by highest rating) -->
            <!-- ================================================= -->

            <div v-if="topTrending.length > 0" class="mb-14">
                <div class="flex items-center justify-between mb-5">
                    <div>
                        <h2 class="text-lg font-bold text-[#101114] flex items-center gap-2 tracking-tight">
                            <span>🔥</span> Top Trending Products
                        </h2>
                        <p class="text-xs text-[#8A8B90] mt-0.5">Highly-rated picks from verified community sellers</p>
                    </div>

                    <Link
                        :href="route('search')"
                        class="text-xs font-semibold text-[#101114] hover:underline flex items-center gap-1"
                    >
                        <span>View all</span>
                        <span>&rarr;</span>
                    </Link>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-5">
                    <Link
                        v-for="product in topTrending"
                        :key="product.id"
                        :href="route('products.show', [product.team.slug, product.slug])"
                        class="group p-3 rounded-2xl bg-white border border-[#EAEAE5] hover:border-[#101114]/30 hover:shadow-md transition-all duration-300 flex flex-col justify-between"
                    >
                        <div>
                            <div class="relative aspect-square rounded-xl bg-[#F0F0EC] overflow-hidden mb-3">
                                <span class="absolute top-2.5 left-2.5 z-10 text-[10px] font-bold px-2.5 py-0.5 rounded-full bg-black/85 text-white backdrop-blur-xs shadow-xs">
                                    Trending
                                </span>

                                <img
                                    v-if="productImage(product)"
                                    :src="productImage(product)"
                                    :alt="product.name"
                                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                />
                                <div v-else class="w-full h-full flex items-center justify-center text-xs text-[#A0A19D]">
                                    No image
                                </div>
                            </div>

                            <p class="text-[11px] font-medium text-[#8A8B90] mb-0.5 truncate">{{ product.team?.name }}</p>
                            <p class="text-sm font-semibold text-[#101114] truncate mb-1 group-hover:text-black">{{ product.name }}</p>
                        </div>

                        <div class="pt-2 mt-auto border-t border-[#F0F0EC] flex items-center justify-between">
                            <span class="text-sm font-bold text-[#101114]">₱{{ Number(product.min_price || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</span>
                            <div class="flex items-center gap-1 text-[11px] text-[#5F5E5A]">
                                <span class="text-amber-500 font-semibold">★</span>
                                <span>{{ product.avg_rating || '5.0' }}</span>
                            </div>
                        </div>
                    </Link>
                </div>
            </div>

            <!-- ================================================= -->
            <!-- BEST SELLERS (by units sold) -->
            <!-- ================================================= -->

            <div class="mb-14">
                <div class="flex items-center justify-between mb-5">
                    <div>
                        <h2 class="text-lg font-bold text-[#101114] flex items-center gap-2 tracking-tight">
                            <span>🏆</span> Best Sellers
                        </h2>
                        <p class="text-xs text-[#8A8B90] mt-0.5">Most in-demand essentials moving fast this week</p>
                    </div>

                    <Link
                        :href="route('search')"
                        class="text-xs font-semibold text-[#101114] hover:underline flex items-center gap-1"
                    >
                        <span>View all</span>
                        <span>&rarr;</span>
                    </Link>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-5">
                    <Link
                        v-for="product in bestSellers"
                        :key="product.id"
                        :href="route('products.show', [product.team.slug, product.slug])"
                        class="group p-3 rounded-2xl bg-white border border-[#EAEAE5] hover:border-[#101114]/30 hover:shadow-md transition-all duration-300 flex flex-col justify-between"
                    >
                        <div>
                            <div class="relative aspect-square rounded-xl bg-[#F0F0EC] overflow-hidden mb-3">
                                <span class="absolute top-2.5 left-2.5 z-10 text-[10px] font-bold px-2.5 py-0.5 rounded-full bg-white/95 text-[#101114] shadow-sm">
                                    Best Seller
                                </span>

                                <img
                                    v-if="productImage(product)"
                                    :src="productImage(product)"
                                    :alt="product.name"
                                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                />
                                <div v-else class="w-full h-full flex items-center justify-center text-xs text-[#A0A19D]">
                                    No image
                                </div>
                            </div>

                            <p class="text-[11px] font-medium text-[#8A8B90] mb-0.5 truncate">{{ product.team?.name }}</p>
                            <p class="text-sm font-semibold text-[#101114] truncate mb-1 group-hover:text-black">{{ product.name }}</p>
                        </div>

                        <div class="pt-2 mt-auto border-t border-[#F0F0EC] flex items-center justify-between">
                            <span class="text-sm font-bold text-[#101114]">₱{{ Number(product.min_price || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</span>
                            <span class="text-[11px] text-[#8A8B90] font-medium">{{ product.sold_count }} sold</span>
                        </div>
                    </Link>
                </div>
            </div>

        </section>

    </GuestLayout>
</template>


<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@600&display=swap');

.font-display {
    font-family: 'Space Grotesk', sans-serif;
}
</style>