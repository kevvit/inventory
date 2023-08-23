<?php
    $itemsPerPage = 20;
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

    /**
     * Calculate the number of total pages based on the filter(s) added to the search
     * 
     * @param int $emailsPerPage The max total number of emails allowed to display on each page
     * @param string $sql The sql statement containing the filter(s), if any
     * @param object $conn Connection to database via mysqli
     * @return int $totalPages The total pages based on the filters
     */
    function calculatePages($sql, $conn) {
        global $itemsPerPage;
        $frompos = strpos($sql, "FROM");
        $orderpos = strpos($sql, "ORDER");
        $substr = substr($sql, $frompos, $orderpos - $frompos);

        $sql = "SELECT count(*) AS total " . $substr;
        $result = $conn->query($sql);
        if ($result === false) {
            echo "Error: " . $sql . "<br>" . $conn->error."<br/>";
            exit(1);
        } elseif ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $totalItems = $row['total'];
        }
        if ($totalItems > 0) {
            $totalPages = ceil($totalItems / $itemsPerPage);
        } else {
            $totalPages = 1;
        }
        return $totalPages;
    }

    /**
     * Adds previous and next arrows for pagination, and page picker. Displays total page count
     * 
     * @param int $currentPage The current page displayed
     * @param int $totalPages The total number of pages given the filter(s)
     * @param int $site The site calling the function
     * @return void
     */
    function pagination($currentPage, $totalPages, $site) {
        echo "<div class=\"pagination hide-on-print\">";
        if ($currentPage > 1) {
            $prevPage = $currentPage - 1;
            echo "<a href=\"$site.php?page={$prevPage}\">◄ Previous</a>";
        }

        $minPage = max(1, $currentPage - 2);
        $maxPage = min($totalPages, $currentPage + 2);

        for ($page = $minPage; $page <= $maxPage; $page++) {
            if ($page == $currentPage) {
                echo "<span class=\"current-page\">$page</span>";
            } else {
                echo "<a href=\"$site.php?page={$page}\">$page</a>";
            }
        }

        if ($currentPage < $totalPages) {
            $nextPage = $currentPage + 1;
            echo "<a href=\"$site.php?page={$nextPage}\">Next ►</a>";
        }

        echo "</div>";

        // Add option to go to a specific page below the numerical pages
        echo "<div class=\"go-form hide-on-print\">";
        echo "<form action=\"$site.php\" method=\"get\">";
        echo "<input type=\"number\" name=\"page\" min=\"1\" max=\"{$totalPages}\" class=\"go-input\" placeholder=\"Go to page\">";
        echo "<input type=\"submit\" value=\"Go\" class=\"go-btn\">";
        echo "</form>";
        echo "</div>";
        
        echo "<h3 class=\"hide-on-print\" style=\"text-align: center;\">" . $totalPages . " pages in total. </h3>";

    }
?>