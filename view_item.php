<?php
    function containsOnlyIntegers($string, $mode) {
        if ($mode == 1) {
            return preg_match('/^[0-9]+(\.[0-9]+)?$/', $string) === 1;
        }
        return preg_match('/^[0-9]+$/', $string) === 1;
    }
    if (isset($_GET['id'])) {
        $servername = "localhost"; # server name
        $username = "root";
        $password = "";
        $database = "gld";
        $conn = new mysqli($servername, $username, $password, $database);
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }
        $id = $_GET['id'];

        # Find the email with the uid as decoded from the url
        $sql = "SELECT * FROM inv WHERE id = $id";
        $info = array();
        $result = $conn->query($sql);
        if ($result === false) {
            echo "Error: " . $sql . "<br>" . $conn->error."<br/>";
        } elseif ($result->num_rows > 0) {
            $info = $result->fetch_assoc();
        } else {
            echo "<h1> ITEM NOT FOUND: </h1>" . $sql;
        }
    } else {
        echo "<h1> ERROR </h1>";
        echo "<p>Item not specified. Use inv.php</p>";
    }

    if ($_POST) {
        if (isset($_POST['save'])) {
            $item = $_POST['item'];
            $brand = $_POST['brand'];
            $supply = $_POST['supply'];
            if ($item == '' || $brand == '' || $supply == '') {
                echo "<script>alert('Item & Brand & Supply required');</script>";
                echo '<script>window.location.href = "view_item.php?id=' . $_GET['id'] . '";</script>';
                exit();
            }
            $quantity = $_POST['quantity'];
            if (!containsOnlyIntegers($quantity, 0)) {
                $quantity = $info['quantity'];
            }
            
            $remindat = $_POST['remindat'];
            if (!containsOnlyIntegers($remindat, 0)) {
                $remindat = $info['remindat'];
            }

            $price = $_POST['price'];
            if (!containsOnlyIntegers($price, 1)) {
                $price = $info['price'];
            }

            $description = $_POST['description'];
            $note = $_POST['note'];
            $status = "Edited";
            $sql = "UPDATE inv set item = ?, brand = ?, supply = ?, quantity = ?, remindat = ?, price = ?, description = ?, note = ? WHERE item = '" . $info['item'] . "' AND brand = '" . $info['brand'] . "' AND supply = '" . $info['supply'] . "'";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssiidss", $item, $brand, $supply, $quantity, $remindat, $price, $description, $note);
            $stmt->execute();

            $currentDatetime = date("Y-m-d H:i:s");
            $sql = "INSERT INTO history (date, item, quantity, brand, supply, remindat, price, description, note, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssissidsss", $currentDatetime, $item, $quantity, $brand, $supply, $remindat, $price, $description, $note, $status);
            $stmt->execute();
        } elseif (isset($_POST['delete'])) {
            updateEmailType("Cancel", $conn, $emailuid, $info);
        }
        header("Location: ". "view_item.php?id=$id");
        exit();
    } 
?>

<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php echo "<title> " . $info['item'] . "</title>" ?>
    <style>
        body, h1, h2, p, textarea {
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, sans-serif;
            background-color: #f7f7f7;
            color: #333;
        }

        .row {
            text-align: center; /* Center-align content within the row */
            padding-bottom: 20px;
        }
        .button-container {
            display: flex;
            justify-content: center;
        }

        button {
            padding: 8px 15px;
            font-size: 14px;
            border: none;
            color: #000;
            cursor: pointer;
            transition: background-color 0.2s, color 0.2s;
            margin: 0 10px; /* Add margin between buttons */
        }

        .save-button {
            background-color: #2ecc71; /* Green color for Save button */
        }
        .delete-button {
            background-color: #e74c3c; /* Red color for Delete button */
        }

        h1 {
            font-size: 36px;
            margin-bottom: 10px;
        }

        main {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        section {
            margin-bottom: 40px;
        }

        h2 {
            font-size: 28px;
            text-align: center;
            margin-bottom: 10px;
        }

        p {
            font-size: 18px;
            text-align: center;
        }
        textarea {
            font-family: "Helvetica Neue", sans-serif; /* Better font choice */
            font-size: 28px; /* Better font size */
            padding: 10px;
            width: 500px;
            border: 1px solid #ccc;
            background-color: white;
            color: #333;
            resize: vertical; /* Allow vertical resizing */
            text-align: center;
            transition: border-color 0.2s;
        }

        button:hover {
            filter: brightness(1.1); /* Slightly increase brightness on hover */
        }

        textarea:focus {
            border-color: #555;
        }
    </style>
</head>
<body>
        <form method = "post">
    <main>
        <section>
                <?php echo "<p> Item (*) </p>";?>
                <div class = "row">
                    <textarea name="item" rows="1" cols="5"><?php echo $info['item']; ?></textarea>
                </div>
                <?php echo "<p> Brand (*) </p>";?>
                <div class = "row">
                    <textarea name="brand" rows="1" cols="5"><?php echo $info['brand']; ?></textarea>
                </div>
                <?php echo "<p> Supply (*) </p>";?>
                <div class = "row">
                    <textarea name="supply" rows="1" cols="5"><?php echo $info['supply']; ?></textarea>
                </div>
                <?php echo "<p> Quantity </p>";?>
                <div class = "row">
                    <textarea name="quantity" rows="1" cols="5"><?php echo $info['quantity']; ?></textarea>
                </div>
                <?php echo "<p> Remind @ </p>";?>
                <div class = "row">
                    <textarea name="remindat" rows="1" cols="5"><?php echo $info['remindat']; ?></textarea>
                </div>
                <?php echo "<p> Price ($) </p>";?>
                <div class = "row">
                    <textarea name="price" rows="1" cols="5"><?php echo $info['price']; ?></textarea>
                </div>
                <?php echo "<p> Description </p>";?>
                <div class = "row">
                    <textarea name="description" rows="3" cols="5"><?php echo $info['description']; ?></textarea>
                </div>
                <?php echo "<p> Note </p>";?>
                <div class = "row">
                    <textarea name="note" rows="3" cols="5"><?php echo $info['note']; ?></textarea>
                </div>
                <div class="row" class="button-container">
                    <button type="submit" name = "save" id = "save" class="save-button">Save</button>
                    <button type="submit" name = "delete" id = "delete" class="delete-button">Delete</button>
                </div>
        </form>
        </section>
    </main>
</body>
</html>