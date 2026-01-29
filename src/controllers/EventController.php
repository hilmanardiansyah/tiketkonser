<?php
namespace Controllers;

class EventController {
  public function index(): void {
    $pdo = \db();
    $stmt = $pdo->query("SELECT id, title, venue, city, event_date, start_time, end_time, status, poster_url, poster_file FROM events ORDER BY created_at DESC");
    $rows = $stmt->fetchAll();
    \json_response($rows);
  }

  public function show(int $id): void {
    $pdo = \db();
    $stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) { \json_response(['error' => 'Event not found'], 404); return; }
    \json_response($row);
  }

  public function ticketTypes(int $eventId): void {
    $pdo = \db();
    $stmt = $pdo->prepare("
      SELECT id, event_id, name, price, quota, sold,
             (quota - sold) AS remaining,
             sales_start, sales_end
      FROM ticket_types
      WHERE event_id = ?
      ORDER BY price ASC
    ");
    $stmt->execute([$eventId]);
    $rows = $stmt->fetchAll();
    \json_response($rows);
  }
}
