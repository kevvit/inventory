<?php
    /**
     * Set up connection to the database using the credentials from config.ini
     * 
     * @return object $conn The connection to the database via mysqli
     * 
     */
    function connSetup() {
        $config = parse_ini_file('config.ini');
        $servername = $config['db_host'];
        $username = $config['db_user'];
        $password = $config['db_password'];
        $database = $config['invdb_name'];

        $conn = new mysqli($servername, $username, $password, $database);
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        } 
        return $conn;
    }

    function containsOnlyIntegers($string, $mode) {
        if ($mode == 1) {
            return preg_match('/^[0-9]+(\.[0-9]+)?$/', $string) === 1;
        }
        return preg_match('/^[0-9]+$/', $string) === 1;
    }

    # Truncates $str so that it has $maxChar max characters followed by '...' and returns it
    function truncate($str, $maxChar) {
        if (!$str) {
            return '';
        }
        if (strlen($str) > $maxChar) {
            return substr($str, 0, $maxChar) . "...";
        } else {
            return $str;
        }
    }

    function updateHistory($conn, $status, $id) {
        $sql = "SELECT * FROM inv WHERE id = $id";
        $result = $conn->query($sql);
        if ($result === false) {
            echo "Error: " . $sql . "<br>" . $conn->error."<br/>";
        } elseif ($result->num_rows > 0) {
            $info = $result->fetch_assoc();
        }
        $item = $info['item'];
        $quantity = $info['quantity'];
        $brand = $info['brand'];
        $supply = $info['supply'];
        $remindat = $info['remindat'];
        $price = $info['price'];
        $description = $info['description'];
        $note = $info['note'];
        $currentDatetime = date("Y-m-d H:i:s");
        $sql = "INSERT INTO history (id, date, item, quantity, brand, supply, remindat, price, description, note, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("dssissidsss", $id, $currentDatetime, $item, $quantity, $brand, $supply, $remindat, $price, $description, $note, $status);
        $stmt->execute();
    }
?>