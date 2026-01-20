<?php
namespace Controllers;

class OrderController {
  public function create(): void {
    $user = \require_auth();
    $userId = (int)($user['id'] ?? 0);
    if ($userId <= 0) {
    \json_response(['error' => 'Unauthorized'], 401);
    return;
    }
    $body = \get_json();
    $ticketTypeId = (int)($body['ticket_type_id'] ?? 0);
    $qty = (int)($body['qty'] ?? 0);

    if ($ticketTypeId <= 0 || $qty <= 0) {
      \json_response(['error' => 'ticket_type_id & qty wajib'], 422);
      return;
    }

    $pdo = \db();

    try {
      $pdo->beginTransaction();

      // lock row supaya aman dari oversell
      $stmt = $pdo->prepare("SELECT id, price, quota, sold FROM ticket_types WHERE id = ? FOR UPDATE");
      $stmt->execute([$ticketTypeId]);
      $tt = $stmt->fetch();

      if (!$tt) {
        $pdo->rollBack();
        \json_response(['error' => 'Ticket type not found'], 404);
        return;
      }

      $remaining = (int)$tt['quota'] - (int)$tt['sold'];
      if ($remaining < $qty) {
        $pdo->rollBack();
        \json_response(['error' => 'Kuota tidak cukup', 'remaining' => $remaining], 409);
        return;
      }

      $unitPrice = (float)$tt['price'];
      $subtotal = $unitPrice * $qty;

      $orderCode = 'ORD-' . date('YmdHis') . '-' . bin2hex(random_bytes(3));

      $stmt = $pdo->prepare("INSERT INTO orders (user_id, order_code, order_date, status, total_amount, created_at, updated_at)
                             VALUES (?, ?, NOW(), 'PENDING', ?, NOW(), NOW())");
      $stmt->execute([(int)$user['id'], $orderCode, $subtotal]);
      $orderId = (int)$pdo->lastInsertId();

      $stmt = $pdo->prepare("INSERT INTO order_items (order_id, ticket_type_id, qty, unit_price, subtotal)
                             VALUES (?, ?, ?, ?, ?)");
      $stmt->execute([$orderId, $ticketTypeId, $qty, $unitPrice, $subtotal]);

      $stmt = $pdo->prepare("UPDATE ticket_types SET sold = sold + ? WHERE id = ?");
      $stmt->execute([$qty, $ticketTypeId]);

      $pdo->commit();

      \json_response([
        'order_code' => $orderCode,
        'status' => 'PENDING',
        'total_amount' => $subtotal
      ], 201);
    } catch (\Throwable $e) {
      if ($pdo->inTransaction()) $pdo->rollBack();
      \json_response(['error' => $e->getMessage()], 500);
    }
  }
  public function showByCode(string $orderCode): void {
  $user = \require_auth();
  $pdo = \db();

  $stmt = $pdo->prepare("SELECT id, user_id, order_code, order_date, status, total_amount FROM orders WHERE order_code = ? LIMIT 1");
  $stmt->execute([$orderCode]);
  $order = $stmt->fetch();

  if (!$order) {
    \json_response(['error' => 'Order not found'], 404);
    return;
  }

  // biar user lain gak bisa lihat order orang
  if ((int)$order['user_id'] !== (int)$user['id']) {
    \json_response(['error' => 'Forbidden'], 403);
    return;
  }

  \json_response($order);
}

public function ticketsByCode(string $orderCode): void {
  $user = \require_auth();
  $pdo = \db();

  $stmt = $pdo->prepare("SELECT id, user_id, status FROM orders WHERE order_code = ? LIMIT 1");
  $stmt->execute([$orderCode]);
  $order = $stmt->fetch();

  if (!$order) {
    \json_response(['error' => 'Order not found'], 404);
    return;
  }

  if ((int)$order['user_id'] !== (int)$user['id']) {
    \json_response(['error' => 'Forbidden'], 403);
    return;
  }

  if ($order['status'] !== 'PAID') {
    \json_response(['error' => 'Order belum PAID'], 409);
    return;
  }

  $stmt = $pdo->prepare("
    SELECT t.ticket_code, t.qr_payload, t.attendee_name, t.status, t.checked_in_at
    FROM tickets t
    JOIN order_items oi ON oi.id = t.order_item_id
    JOIN orders o ON o.id = oi.order_id
    WHERE o.order_code = ?
    ORDER BY t.id ASC
  ");
  $stmt->execute([$orderCode]);
  $tickets = $stmt->fetchAll();

  \json_response([
    'order_code' => $orderCode,
    'tickets' => $tickets
  ]);
}

}
