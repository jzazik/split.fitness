let loadPromise = null;

const LOAD_TIMEOUT_MS = 8000;

/**
 * Loads the Yandex Maps v3 script once and resolves when ymaps3 is ready.
 * SSR-safe: returns null when called on the server.
 * Rejects after LOAD_TIMEOUT_MS if the script is blocked or unreachable.
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

    let settled = false;
    const timeout = setTimeout(() => {
      if (!settled) {
        settled = true;
        loadPromise = null;
        reject(new Error('Yandex Maps script load timed out — possibly blocked by an ad blocker'));
      }
    }, LOAD_TIMEOUT_MS);

    const script = document.createElement('script');
    script.src = `https://api-maps.yandex.ru/v3/?apikey=${apiKey}&lang=ru_RU`;
    script.async = true;

    script.onload = () => {
      if (settled) return;
      window.ymaps3.ready
        .then((api) => {
          if (!settled) { settled = true; clearTimeout(timeout); resolve(api); }
        })
        .catch((err) => {
          if (!settled) { settled = true; clearTimeout(timeout); reject(err); }
        });
    };
    script.onerror = () => {
      if (settled) return;
      settled = true;
      clearTimeout(timeout);
      loadPromise = null;
      reject(new Error('Failed to load Yandex Maps script'));
    };

    document.head.appendChild(script);
  });

  return loadPromise;
}
