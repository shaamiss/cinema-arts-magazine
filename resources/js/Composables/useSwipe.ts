import { ref, onMounted, onUnmounted, type Ref } from 'vue';

export interface UseSwipeOptions {
    threshold?: number;
    onSwipeLeft?: () => void;
    onSwipeRight?: () => void;
}

export function useSwipe(target: Ref<HTMLElement | null>, options: UseSwipeOptions = {}) {
    const { threshold = 50, onSwipeLeft, onSwipeRight } = options;

    const startX = ref(0);
    const startY = ref(0);
    const isSwiping = ref(false);

    const onTouchStart = (e: TouchEvent) => {
        if (e.touches.length !== 1) return;
        startX.value = e.touches[0].clientX;
        startY.value = e.touches[0].clientY;
        isSwiping.value = true;
    };

    const onTouchEnd = (e: TouchEvent) => {
        if (!isSwiping.value || e.changedTouches.length !== 1) return;
        isSwiping.value = false;

        const deltaX = e.changedTouches[0].clientX - startX.value;
        const deltaY = e.changedTouches[0].clientY - startY.value;

        // Ensure horizontal intent (more horizontal than vertical)
        if (Math.abs(deltaX) > Math.abs(deltaY) && Math.abs(deltaX) > threshold) {
            if (deltaX < 0) {
                // Swiped Left
                onSwipeLeft?.();
            } else {
                // Swiped Right
                onSwipeRight?.();
            }
        }
    };

    onMounted(() => {
        const el = target.value;
        if (el) {
            el.addEventListener('touchstart', onTouchStart, { passive: true });
            el.addEventListener('touchend', onTouchEnd, { passive: true });
        }
    });

    onUnmounted(() => {
        const el = target.value;
        if (el) {
            el.removeEventListener('touchstart', onTouchStart);
            el.removeEventListener('touchend', onTouchEnd);
        }
    });

    return {
        isSwiping,
    };
}
