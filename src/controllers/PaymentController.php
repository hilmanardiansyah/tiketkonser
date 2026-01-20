<?php
namespace Controllers;

class PaymentController {
  public function confirm(): void {
    $user = \require_auth();

    $body = \get_json();
    $orderCode = trim((string)($body['order_code'] ?? ''));
    $method = trim((string)($body['method'] ?? 'transfer'));

    if ($orderCode === '') {
      \json_response(['error' => 'order_code wajib'], 422);
      return;
    }

    $pdo = \db();

    try {
      $pdo->beginTransaction();

      $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_code = ? FOR UPDATE");
      $stmt->execute([$orderCode]);
      $order = $stmt->fetch();

      if (!$order) {
        $pdo->rollBack();
        \json_response(['error' => 'Order not found'], 404);
        return;
      }

      if ($order['status'] !== 'PENDING') {
        $pdo->rollBack();
        \json_response(['error' => 'Order status bukan PENDING'], 409);
        return;
      }

      // simpan payment
      $paymentRef = 'PAY-' . date('YmdHis') . '-' . bin2hex(random_bytes(3));
      $stmt = $pdo->prepare("INSERT INTO payments (order_id, method, payment_ref, amount, status, paid_at)
                             VALUES (?, ?, ?, ?, 'PAID', NOW())");
      $stmt->execute([(int)$order['id'], $method, $paymentRef, (float)$order['total_amount']]);

      // update order
      $stmt = $pdo->prepare("UPDATE orders SET status='PAID', updated_at=NOW() WHERE id=?");
      $stmt->execute([(int)$order['id']]);

      // generate tickets (1 per qty)
      $stmt = $pdo->prepare("
        SELECT oi.id AS order_item_id, oi.qty
        FROM order_items oi
        WHERE oi.order_id = ?
      ");
      $stmt->execute([(int)$order['id']]);
      $items = $stmt->fetchAll();

      $ticketInsert = $pdo->prepare("
        INSERT INTO tickets (order_item_id, ticket_code, qr_payload, attendee_name, status)
        VALUES (?, ?, ?, NULL, 'ACTIVE')
      ");

      $createdTickets = [];
      foreach ($items as $it) {
        $qty = (int)$it['qty'];
        for ($i = 0; $i < $qty; $i++) {
          $ticketCode = 'TIX-' . date('YmdHis') . '-' . bin2hex(random_bytes(4));
          $qrPayload = json_encode(['ticket_code' => $ticketCode, 'order_code' => $orderCode], JSON_UNESCAPED_UNICODE);

          $ticketInsert->execute([(int)$it['order_item_id'], $ticketCode, $qrPayload]);
          $createdTickets[] = $ticketCode;
        }
      }

      $pdo->commit();

      \json_response([
        'order_code' => $orderCode,
        'status' => 'PAID',
        'payment_ref' => $paymentRef,
        'tickets' => $createdTickets
      ]);
    } catch (\Throwable $e) {
      if ($pdo->inTransaction()) $pdo->rollBack();
      \json_response(['error' => $e->getMessage()], 500);
    }
  }
}
