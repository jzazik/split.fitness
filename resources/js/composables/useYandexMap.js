let loadPromise = null;

/**
 * Loads the Yandex Maps v3 script once and resolves when ymaps3 is ready.
 * SSR-safe: returns null when called on the server.
 */
export function loadYandexMaps() {
  if (typeof window === 'undefined') return Promise.resolve(null);

  if (loadPromise) return loadPromise;

  loadPromise = new Promise((resolve, reject) => {
    if (window.ymaps3) {
      window.ymaps3.ready.then(() => resolve(window.ymaps3)).catch(reject);
      return;
    }

    const apiKey = import.meta.env.VITE_YANDEX_MAPS_APIKEY;
    if (!apiKey) {
      reject(new Error('VITE_YANDEX_MAPS_APIKEY is not set'));
      return;
    }

    const script = document.createElement('script');
    script.src = `https://api-maps.yandex.ru/v3/?apikey=${apiKey}&lang=ru_RU`;
    script.async = true;

    script.onload = () => {
      window.ymaps3.ready.then(() => resolve(window.ymaps3)).catch(reject);
    };
    script.onerror = () => {
      loadPromise = null;
      reject(new Error('Failed to load Yandex Maps script'));
    };

    document.head.appendChild(script);
  });

  return loadPromise;
}
