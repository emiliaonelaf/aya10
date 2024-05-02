<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Заказ билетов</title>
</head>
<body>
    <h2>Заказ билетов</h2>

    <form id="orderForm" method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>">
        <label for="event">Выберите событие:</label>
        <select name="event" id="event" onchange="fillEventDetails()">
            <?php
            
            $db = new SQLite3('database.sqlite3');

            
            $events_query = $db->query('SELECT event_id, name, date, price, total_tickets FROM events');

            
            while ($event = $events_query->fetchArray()) {
                $tickets_left = $event['total_tickets'] > 0 ? $event['total_tickets'] : 'Продано';
                echo "<option value='{$event['event_id']}' data-date='{$event['date']}' data-price='{$event['price']}' data-total='{$event['total_tickets']}'>{$event['name']} ($tickets_left)</option>";
            }
            ?>
        </select>

        <label for="date">Дата:</label>
        <input type="text" id="date" name="date" readonly>

        <label for="price">Цена:</label>
        <input type="text" id="price" name="price" readonly>

        <label for="full_name">Ф.И.О.:</label>
        <input type="text" id="full_name" name="full_name" required>

        <button type="submit" name="submit">Купить билет</button>
    </form>

    <script>
        function fillEventDetails() {
            var eventSelect = document.getElementById('event');
            var selectedOption = eventSelect.options[eventSelect.selectedIndex];

            var dateInput = document.getElementById('date');
            var priceInput = document.getElementById('price');

            dateInput.value = selectedOption.getAttribute('data-date');
            priceInput.value = selectedOption.getAttribute('data-price');
        }
    </script>

    <?php
    if (isset($_POST['submit'])) {
        $event_id = $_POST['event'];
        $full_name = $_POST['full_name'];

        
        $db = new SQLite3('database.sqlite3');

        
        $event_query = $db->prepare('SELECT name, price, total_tickets FROM events WHERE event_id = :id');
        $event_query->bindValue(':id', $event_id, SQLITE3_INTEGER);
        $event_result = $event_query->execute();
        $event_info = $event_result->fetchArray(SQLITE3_ASSOC);

        
        if ($event_info['total_tickets'] > 0) {
            
            $updated_tickets = $event_info['total_tickets'] - 1;

            
            $update_query = $db->prepare('UPDATE events SET total_tickets = :total WHERE event_id = :id');
            $update_query->bindValue(':total', $updated_tickets, SQLITE3_INTEGER);
            $update_query->bindValue(':id', $event_id, SQLITE3_INTEGER);
            $update_query->execute();

            
            $insert_query = $db->prepare('INSERT INTO orders (full_name, event_id, event_name, event_ticket_price) VALUES (:full_name, :event_id, :event_name, :event_ticket_price)');
            $insert_query->bindValue(':full_name', $full_name, SQLITE3_TEXT);
            $insert_query->bindValue(':event_id', $event_id, SQLITE3_INTEGER);
            $insert_query->bindValue(':event_name', $event_info['name'], SQLITE3_TEXT);
            $insert_query->bindValue(':event_ticket_price', $event_info['price'], SQLITE3_FLOAT);
            $insert_query->execute();

            echo "<p>Спасибо за покупку билета на {$event_info['name']}!</p>";
        } else {
            echo "<p>Извините, билетов на {$event_info['name']} распроданы!</p>";
        }

        
        $db->close();
    }
    ?>
</body>
</html>
