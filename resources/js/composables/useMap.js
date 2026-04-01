import { ref } from 'vue';

/**
 * Composable for map-related utilities, including reverse geocoding.
 */
export function useMap() {
  const isLoading = ref(false);
  const error = ref(null);
  let debounceTimer = null;

  /**
   * Reverse geocode coordinates to get human-readable address
   * Uses OpenStreetMap Nominatim API with 1 req/sec rate limiting
   *
   * @param {number} lat - Latitude
   * @param {number} lng - Longitude
   * @returns {Promise<{display_name: string, address: object}|null>}
   */
  const reverseGeocode = (lat, lng) => {
    return new Promise((resolve) => {
      // Clear any pending requests
      if (debounceTimer) {
        clearTimeout(debounceTimer);
      }

      // Debounce: wait 1 second before making request (Nominatim rate limit)
      debounceTimer = setTimeout(async () => {
        isLoading.value = true;
        error.value = null;

        try {
          const url = `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&addressdetails=1`;

          const response = await fetch(url, {
            headers: {
              'User-Agent': 'SplitFitness/1.0 (workout booking app)',
            },
          });

          if (!response.ok) {
            throw new Error(`Nominatim API error: ${response.status}`);
          }

          const data = await response.json();

          if (data.error) {
            throw new Error(data.error);
          }

          resolve({
            display_name: data.display_name,
            address: data.address,
          });
        } catch (err) {
          console.warn('Reverse geocoding failed:', err.message);
          error.value = err.message;
          resolve(null);
        } finally {
          isLoading.value = false;
        }
      }, 1000); // 1 second debounce to respect Nominatim rate limit
    });
  };

  return {
    reverseGeocode,
    isLoading,
    error,
  };
}
