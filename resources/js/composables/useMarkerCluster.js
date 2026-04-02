/**
 * Composable for Yandex Maps v3 marker clustering.
 * Loads @yandex/ymaps3-clusterer via npm package (not ymaps3.import).
 */
export async function useMarkerCluster() {
  const { YMapClusterer, clusterByGrid } = await import('@yandex/ymaps3-clusterer');

  return {
    YMapClusterer,
    clusterByGrid,
  };
}
