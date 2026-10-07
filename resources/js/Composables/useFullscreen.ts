import { ref, onMounted, onUnmounted } from 'vue';

export function useFullscreen() {
    const isFullscreen = ref(false);

    const updateFullscreenState = () => {
        isFullscreen.value = Boolean(
            document.fullscreenElement ||
            (document as any).webkitFullscreenElement ||
            (document as any).mozFullScreenElement ||
            (document as any).msFullscreenElement
        );
    };

    const enterFullscreen = async (el?: HTMLElement) => {
        const target = el || document.documentElement;
        try {
            if (target.requestFullscreen) {
                await target.requestFullscreen();
            } else if ((target as any).webkitRequestFullscreen) {
                await (target as any).webkitRequestFullscreen();
            } else if ((target as any).msRequestFullscreen) {
                await (target as any).msRequestFullscreen();
            }
        } catch (err) {
            console.warn('Fullscreen request failed:', err);
        }
    };

    const exitFullscreen = async () => {
        try {
            if (document.exitFullscreen) {
                await document.exitFullscreen();
            } else if ((document as any).webkitExitFullscreen) {
                await (document as any).webkitExitFullscreen();
            } else if ((document as any).msExitFullscreen) {
                await (document as any).msExitFullscreen();
            }
        } catch (err) {
            console.warn('Exit fullscreen failed:', err);
        }
    };

    const toggleFullscreen = async (el?: HTMLElement) => {
        if (isFullscreen.value) {
            await exitFullscreen();
        } else {
            await enterFullscreen(el);
        }
    };

    onMounted(() => {
        document.addEventListener('fullscreenchange', updateFullscreenState);
        document.addEventListener('webkitfullscreenchange', updateFullscreenState);
        document.addEventListener('mozfullscreenchange', updateFullscreenState);
        document.addEventListener('MSFullscreenChange', updateFullscreenState);
    });

    onUnmounted(() => {
        document.removeEventListener('fullscreenchange', updateFullscreenState);
        document.removeEventListener('webkitfullscreenchange', updateFullscreenState);
        document.removeEventListener('mozfullscreenchange', updateFullscreenState);
        document.removeEventListener('MSFullscreenChange', updateFullscreenState);
    });

    return {
        isFullscreen,
        enterFullscreen,
        exitFullscreen,
        toggleFullscreen,
    };
}
