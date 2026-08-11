<?php

/**
 * The pickup point feed is ~1.6 MB / 1200+ points, so decoding it once per request and
 * reusing the result matters: a single checkout request asks for it several times.
 */
function venipak_fetch_pickups($country = null) {
  static $collection = null;

  if ($collection === null) {
    $collection = _venipak_read_pickups();
  }

  if ($country === null) {
    return $collection;
  }

  $result = [];
  foreach ($collection as $point) {
    if ($point['country'] === $country) {
      $result[] = $point;
    }
  }
  return $result;
}

function _venipak_read_pickups() {
  $upload_dir = wp_upload_dir();
  if (!file_exists($upload_dir['basedir'] . '/venipak')) {
    mkdir($upload_dir['basedir'] . '/venipak', 0755, true);
  }
  $file_path = $upload_dir['basedir'] . '/venipak/pickups.json';
  if (!file_exists($file_path) || (time() - filemtime($file_path) > 86400)) {
    _venipak_fetch_pickups_request($file_path);
  }

  // Still missing means the very first fetch failed — there is nothing to read yet.
  if (!file_exists($file_path)) {
    return [];
  }

  $contents = file_get_contents($file_path);
  if ($contents === false) {
    return [];
  }
  return json_decode($contents, true) ?: [];
}

function _venipak_fetch_pickups_request($file_path) {
  $response = wp_remote_get( "https://go.venipak.lt/ws/get_pickup_points" );

  if (is_wp_error( $response ) ) {
      error_log("VENIPAK https://go.venipak.lt/ws/get_pickup_points SERVICE ERROR");
  }
  $body = wp_remote_retrieve_body( $response );
  $collection = json_decode($body);

  if ($collection && sizeof($collection) > 0) {
    file_put_contents($file_path, $body);
  } else {
    error_log("VENIPAK https://go.venipak.lt/ws/get_pickup_points RESPONSE READ ERROR");
  }
}

function venipak_find_pickup_by_id($point_id) {
  if (!is_numeric($point_id)) {
    return false;
  }

  foreach (venipak_fetch_pickups() as $point) {
    if ($point['id'] == $point_id) {
      return $point;
    }
  }
  return false;
}

/**
 * The part of a pickup point the plugin still needs after checkout: the consignee block of
 * the import XML, plus the strings shown on the order screen and in customer emails.
 * Storing the whole point would put ~1.4 KB into postmeta per order, over half of it
 * working_hours that nothing reads back.
 */
function venipak_pickup_snapshot($point) {
  $keys = ['id', 'code', 'name', 'display_name', 'country', 'city', 'address', 'zip'];
  $snapshot = [];
  foreach ($keys as $key) {
    $snapshot[$key] = isset($point[$key]) ? $point[$key] : '';
  }
  return $snapshot;
}

/**
 * Record the customer's choice. The id stays in its own meta because it is the value of the
 * select2 control on the order screen and of the checkout field; the snapshot rides along so
 * the order still knows where it was going once the point leaves Venipak's feed.
 *
 * Does not save the order — the caller does, as it did before.
 */
function venipak_store_order_pickup($order, $point_id) {
  $point_id = trim((string) $point_id);

  if ($point_id === '') {
    venipak_clear_order_pickup($order);
    return false;
  }

  $previous_id = trim((string) $order->get_meta('venipak_pickup_point'));
  $order->update_meta_data('venipak_pickup_point', $point_id);

  $point = venipak_find_pickup_by_id($point_id);
  if ($point) {
    $order->update_meta_data('venipak_pickup_point_data', wp_json_encode(venipak_pickup_snapshot($point)));
  } elseif ($previous_id !== $point_id) {
    // A different point that the feed does not know: never leave the old point's snapshot
    // paired with the new id. An unchanged id keeps its snapshot — that is the whole point
    // of having one, and re-saving an order must not throw it away.
    $order->delete_meta_data('venipak_pickup_point_data');
  }

  return $point;
}

function venipak_clear_order_pickup($order) {
  $order->delete_meta_data('venipak_pickup_point');
  $order->delete_meta_data('venipak_pickup_point_data');
}

/**
 * Resolve an order's pickup point.
 *
 * The live feed wins: a point can be renamed or relocated while keeping its id, and the
 * parcel has to go where it is now. The checkout snapshot is the fallback for points that
 * have since left the feed. $source reports which one answered ('live', 'snapshot',
 * 'legacy' or null) so callers can flag a choice that no longer exists instead of quietly
 * acting on it.
 */
function venipak_resolve_order_pickup($order, &$source = null) {
  $source = null;
  $point_id = $order->get_meta('venipak_pickup_point');

  if (is_numeric($point_id)) {
    $point = venipak_find_pickup_by_id($point_id);
    if ($point) {
      $source = 'live';
      return $point;
    }

    $snapshot = json_decode($order->get_meta('venipak_pickup_point_data'), true);
    if (is_array($snapshot) && !empty($snapshot['code'])) {
      $source = 'snapshot';
      return $snapshot;
    }

    return false;
  }

  // Orders written before the id-only meta stored the whole point as JSON.
  if (is_string($point_id) && $point_id !== '') {
    $legacy = json_decode($point_id, true);
    if (is_array($legacy) && !empty($legacy['code'])) {
      $source = 'legacy';
      return $legacy;
    }
  }

  return false;
}
