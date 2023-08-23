<?php
    require_once("invhelper.php");
    
    if (isset($_GET['id'])) {
        $conn = connSetup();
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
            echo "<h1> ITEM DOES NOT EXIST OR DELETED</h1>";
            exit();
        }
    } else {
        echo "<h1> ERROR </h1>";
        echo "<p>Item not specified. Use inv.php</p>";
        $exit();
    }

    if ($_POST) {
        if (isset($_POST['save'])) {
            $item = $_POST['item'];
            $brand = $_POST['brand'];
            $supply = $_POST['supply'];
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
            $sql = "UPDATE inv set item = ?, brand = ?, supply = ?, quantity = ?, remindat = ?, price = ?, description = ?, note = ? WHERE id = $id";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssiidss", $item, $brand, $supply, $quantity, $remindat, $price, $description, $note);
            $stmt->execute();

            $currentDatetime = date("Y-m-d H:i:s");
            $sql = "INSERT INTO history (id, date, item, quantity, brand, supply, remindat, price, description, note, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("dssissidsss", $id, $currentDatetime, $item, $quantity, $brand, $supply, $remindat, $price, $description, $note, $status);
            $stmt->execute();
        } elseif (isset($_POST['delete'])) {
            $sql = "SELECT * FROM inv WHERE id = $id";
            $result = $conn->query($sql);
            if ($result === false) {
                echo "Error: " . $sql . "<br>" . $conn->error."<br/>";
            } elseif ($result->num_rows > 0) {
                $info = $result->fetch_assoc();
            }

            $status = "DELETED";
            updateHistory($conn, $status, $id);
            $sql = "DELETE FROM inv WHERE id = $id";
            $result = $conn->query($sql);

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
        <link rel="stylesheet" type="text/css" href="invstyles.css">
    </head>
    <body>
        <form method = "post">
            <main>
                <section>
                        <?php echo "<p> Item</p>";?>
                        <div class = "row">
                            <textarea name="item" rows="1" cols="5"><?php echo $info['item']; ?></textarea>
                        </div>
                        <?php echo "<p> Brand</p>";?>
                        <div class = "row">
                            <textarea name="brand" rows="1" cols="5"><?php echo $info['brand']; ?></textarea>
                        </div>
                        <?php echo "<p> Supply</p>";?>
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
                            <button type="submit" name = "save" id = "save" class="save-button hide-on-print">Save</button>
                            <button type="submit" name = "delete" id = "delete" class="delete-button hide-on-print">Delete</button>
                        </div>
                </section>
            </main>
        </form>
        <?php
            $sql = "SELECT * FROM history WHERE id = $id ORDER BY date DESC";
            $info = array();
            $result = $conn->query($sql);
            if ($result === false) {
                echo "Error: " . $sql . "<br>" . $conn->error."<br/>";
            } elseif ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    array_push($info, $row);
                }
            }

        ?>
        <div class = "centered-container">
            <table border="1">
                <tr style="background-color: #eee;">
                    <th>DATE</th>
                    <th>ITEM</th>
                    <th>QUANTITY</th>
                    <th>REMIND @</th>
                    <th>BRAND</th>
                    <th>SUPPLY</th>
                    <th>PRICE</th>
                    <th>DESCRIPTION</th>
                    <th>NOTE</th>
                    <th>STATUS</th>
                </tr>
                
                <?php
                // Set the background color of the table
                    $colour = "#c3cde6";
                    foreach ($info as $row){
                        $row['description'] = truncate($row['description'], 50);
                        $row['note'] = truncate($row['note'], 50);
                ?>
                <tr bgcolor="<?= $colour ?>">
                    <td class="center"><?= $row['date'] ?></td>
                    <td class="center"><?= $row['item'] ?></td>
                    <td class="center"><?= $row['quantity'] ?></td>
                    <td class="center"><?= $row['remindat'] ?></td>
                    <td class="center"><?= $row['brand'] ?></td>
                    <td class="center"><?= $row['supply'] ?></td>
                    <td class="center"><?= $row['price'] ?></td>
                    <td class="center"><?= $row['description'] ?></td>
                    <td class="center"><?= $row['note'] ?></td>
                    <td class="center"><?= $row['status'] ?></td>
                </tr>
                <?php
                    }
                ?>
            </table>
            <br><br><br>
        </div>
    </body>
</html>