/**
 * Composable for marker clustering functionality
 * SSR-safe: expects L (leaflet) to be passed as parameter after dynamic import
 */
export function useMarkerCluster(L) {
  if (!L) {
    throw new Error('useMarkerCluster: Leaflet instance is required');
  }
  /**
   * Create a marker cluster group with custom cluster icon
   * @param {Object} options - Additional options for markerClusterGroup
   * @returns {L.MarkerClusterGroup}
   */
  const createClusterGroup = (options = {}) => {
    return L.markerClusterGroup({
      // Custom icon creator function
      iconCreateFunction: (cluster) => {
        const childCount = cluster.getChildCount();
        let className = 'marker-cluster-';

        // Size classes based on marker count
        if (childCount < 10) {
          className += 'small';
        } else if (childCount < 50) {
          className += 'medium';
        } else {
          className += 'large';
        }

        return L.divIcon({
          html: `<div><span>${childCount}</span></div>`,
          className: `marker-cluster ${className}`,
          iconSize: L.point(40, 40),
        });
      },
      // Disable clustering at max zoom
      disableClusteringAtZoom: 18,
      // Show coverage on hover
      showCoverageOnHover: true,
      // Zoom to bounds animation
      zoomToBoundsOnClick: true,
      // Spiderfy options
      spiderfyOnMaxZoom: true,
      removeOutsideVisibleBounds: true,
      // Merge custom options
      ...options,
    });
  };

  return {
    createClusterGroup,
  };
}
