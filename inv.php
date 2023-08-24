<?php
    session_start();
    #session_destroy();
    require_once("invhelper.php");
    $conn = connSetup();
    $all = false;
    if (isset($_POST['itemsPerPage'])) {
        $itemsPerPage = $_POST['itemsPerPage'];
    } elseif (isset($_SESSION['itemsPerPage'])) {
        $itemsPerPage = $_SESSION['itemsPerPage'];
    }
    if ($itemsPerPage == 'ALL') {
        $all = true;
    }
	$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    if (!$all) $startIndex = ($currentPage - 1) * $itemsPerPage;
    $info = array();
    if (!(isset($_SESSION['curTable']))) $_SESSION['curTable'] = "inv";
    if ($_SESSION['curTable'] == "history") {
        $sql = "SELECT * FROM history ORDER BY date DESC";
    } else {
        $sql = "SELECT * FROM inv order by (quantity <= remindat) desc, quantity";
    }
    if ($_POST) {
        foreach($_POST as $key => $value) {
            if (strpos($key, 'quantity') !== false) {
                $parts = explode('_', $key);
                $id = $parts[1];
                $old_quantity = $parts[2];
                $quantity = $value;
            } else if (strpos($key, 'remove') !== false) {
                $parts = explode('_', $key);
                $id = $parts[1];
                $edit = '-';
            } else if (strpos($key, 'add') !== false) {
                $parts = explode('_', $key);
                $id = $parts[1];
                $edit = '+';
            }
        }
        if (isset($id)) {
            if ($edit === '+') {
                $quantity += $old_quantity;
                $status = "+ QTY";
            } else {
                $quantity = $old_quantity - $quantity;
                $status = "- QTY";
            }
            $sql = "UPDATE inv SET quantity = $quantity WHERE id = $id";
            $result = $conn->query($sql);

            updateHistory($conn, $status, $id);
            $sql = "SELECT * FROM inv order by (quantity <= remindat) desc, quantity";
			header("Location: inv.php?page=$currentPage");
        }
        if (isset($_POST['itembtn'])) {
            $_SESSION['curTable'] = "inv";
            $_SESSION['itemsPerPage'] = $_POST['itemsPerPage'];
            $item = $_POST['itemSelect'] ?? 'None';
            $sql = "SELECT * FROM inv";
            if ($item != '') {
                $sql = $sql . " WHERE item LIKE '%$item%'";
                $_SESSION['itemSelect'] = $item;
            }
            $sql = $sql . " ORDER BY (quantity <= remindat) desc, quantity";
            $currentPage = 1;
			$startIndex = 0;
        } elseif (isset($_POST['historybtn'])) {
            $_SESSION['curTable'] = "history";
            $_SESSION['itemsPerPage'] = $_POST['itemsPerPage'];
            $item = $_POST['itemSelect'] ?? '';
            $afterDate = $_POST['afterdate'] ?? '';
            $beforeDate = $_POST['beforedate'] ?? '';
            $sql = "SELECT * FROM history WHERE id > 0";
            if ($item != '') {
                $sql = $sql . " AND item LIKE '%$item%'";
                $_SESSION['itemSelect'] = $item;
            }
            $dateTime = DateTime::createFromFormat("m/d/Y", $afterDate);
            if ($dateTime === false) {
                $afterDate = '';
            } else {
                $afterDate = $dateTime->format("Y-m-d");
                $sql = $sql . " AND date >= '$afterDate'";
                $_SESSION['afterdate'] = $afterDate;
            }
            $dateTime = DateTime::createFromFormat("m/d/Y", $beforeDate);
            if ($dateTime === false) {
                $beforeDate = '';
            } else {
                $beforeDate = $dateTime->format("Y-m-d");
                $sql = $sql . " AND date <= '$beforeDate'";
                $_SESSION['beforedate'] = $beforeDate;
            }
            $sql = $sql . " ORDER BY date DESC";
            $currentPage = 1;
			$startIndex = 0;
        } elseif (isset($_POST['clear'])) {
            $_POST['itemSelect'] = '';
            $_POST['afterdate'] = '';
            $_POST['beforedate'] = '';
            $_SESSION['itemSelect'] = '';
            $_SESSION['afterdate'] = '';
            $_SESSION['beforedate'] = '';
			header("Location: inv.php?page=1");
        } elseif (isset($_POST['insertbtn'])) {
            $item = ($_POST['item'] ?? 'None');
            if ($item === '') {
                $item = 'None';
            }
            $quantity = 0;
            $brand = ($_POST['brand'] ?? '');
            $supply = ($_POST['supply'] ?? '');
            $price = ($_POST['price'] ?? '');
            $description = ($_POST['description'] ?? '');
            
            // Insert into item list
            $sql = "INSERT INTO inv (item, quantity, brand, supply, price, description) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sissds", $item, $quantity, $brand, $supply, $price, $description);
            $stmt->execute();
            $info = array();
            $sql = "SELECT * FROM inv ORDER BY inserted_at DESC LIMIT 1";
            $result = $conn->query($sql);
            if ($result === false) {
                echo "Error: " . $sql . "<br>" . $conn->error."<br/>";
            } elseif ($result->num_rows > 0) {
                $id = $result->fetch_assoc()['id'];
            }

            $currentDatetime = date("Y-m-d H:i:s");
            $remindat = '0';
            $note = '';
            $status = "New";
            $sql = "INSERT INTO history (id, date, item, quantity, brand, supply, remindat, price, description, note, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("dssissidsss", $id, $currentDatetime, $item, $quantity, $brand, $supply, $remindat, $price, $description, $note, $status);
            $stmt->execute();
            $sql = "SELECT * FROM inv order by (quantity <= remindat) desc, quantity";
			header("Location: inv.php?page=1");
        }
    }
    if (!$all) $sql = $sql. " LIMIT " . $itemsPerPage . " OFFSET " . $startIndex;
    $info = retrieveAllEmails($conn, $sql);
    if (!$all) $totalPages = calculatePages($sql, $conn);

?>

<html>
    <head>
        <link rel="stylesheet" type="text/css" href="invstyles.css">
        <title>INV</title>
        <link rel="stylesheet" href="//code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
        <link rel="stylesheet" href="/resources/demos/style.css">
        <script src="https://code.jquery.com/jquery-3.6.0.js"></script>
        <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.js"></script>
        <script>
            $( function() {
                $( ".datepicker" ).datepicker();
            } );
        </script>
    </head>
    <body>
        <div class = "centered-container">
            <br>
            <?php
                if ($_SESSION['curTable'] == "history") { ?>
                    <h2 class="hide-on-print">INVENTORY HISTORY</h2>
            <?php } else { ?>
                    <h2 class="hide-on-print">INVENTORY</h2>
            <?php }?>
            <br>

            <!--SELECTION TABLE -->
            <table border="1" class="hide-on-print">
                <tr>
                    <td style="background-color: #e9ebf4;">
                        <form name="searchForm" method="POST">
                            <b> ITEM:</b> <input name="itemSelect" type="text" style="height:25pt;width:100pt;" value="<?php echo isset($_SESSION['itemSelect']) ? $_SESSION['itemSelect'] : '' ?>">
                             <?php
                                if ($_SESSION['curTable'] == "history") {
                            ?>
                                    <b> AFTER:</b> <input name="afterdate" class="datepicker" style="height:25pt;width:100pt;margin-right:20px;" value="<?php echo isset($_SESSION['afterdate']) ? $_SESSION['afterdate'] : '' ?>">
                                    <b> BEFORE:</b> <input name="beforedate" class="datepicker" style="height:25pt;width:100pt;margin-right:20px;" value="<?php echo isset($_SESSION['beforedate']) ? $_SESSION['beforedate'] : '' ?>">
                            <?php 
                                }
                             ?>
                            <input type="submit" name="itembtn" value="ITEMS" id="itemgo" />
                            <input type="submit" name="historybtn" value="HISTORY" id="historygo" />
                            <input type="submit" name="clear" class="marg" value="CLEAR" id="cleargo" />
                            <b>ITEMS/PAGE:</b> 
                            <select name="itemsPerPage" style="height:25pt;width:100pt;">
                                <option <?php if (isset($_SESSION['itemsPerPage']) && $_SESSION['itemsPerPage'] == 'ALL') echo "selected";?>>ALL</option>
                                <option <?php if (isset($_SESSION['itemsPerPage']) && $_SESSION['itemsPerPage'] == '20') echo "selected";?>>20</option>
                                <option <?php if (isset($_SESSION['itemsPerPage']) && $_SESSION['itemsPerPage'] == '50') echo "selected";?>>50</option>
                                <option <?php if (isset($_SESSION['itemsPerPage']) && $_SESSION['itemsPerPage'] == '100') echo "selected";?>>100</option>
                            </select> 
                            <br>
                        </form>
                    </td>
                </tr>
            </table>

            <!-- NEW ITEM TABLE -->
            <table border="1" class="hide-on-print">
                <tr>
                    <td style="background-color: #e9ebf4;">
                            <form name="inputForm" method="POST">
                            <b> ITEM:</b> <input name="item" type="text" style="height:25pt;width:100pt;">
                            <b> BRAND:	</b> <input name="brand" type="text" style="height:25pt;width:100pt;">
                            <b> SUPPLY:	</b> <input name="supply" type="text" style="height:25pt;width:100pt;">
                            <b> PRICE:</b> <input class="marg" name="price" type="number" step='0.01' style="height:25pt;width:100pt;">
                            <b> DESCRIPTION:	</b> <input name="description" type="text" style="height:25pt;width:100pt;">
                            <input type="submit" name="insertbtn" value="INSERT NEW ITEM" id="insertgo" />
                            <br>
                        </form>
                    </td>
                </tr>
            </table>


            <?php if ($_SESSION['curTable'] == "history") { ?>

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
                    <td class="center"><a href="view_item.php?id=<?= $row['id'] ?>" target="_blank"><?= $row['item'] ?></a></td>
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
	        <?php } else { ?>

		    <table border="1">
                <tr style="background-color: #eee;">
                    <th>ITEM</th>
                    <th>QUANTITY</th>
                    <th>BRAND</th>
                    <th>SUPPLY</th>
                    <th>PRICE</th>
                    <th>DESCRIPTION</th>
                    <th>NOTE</th>
                    <th class="hide-on-print">+/-</th>
                </tr>
	
	        <?php
	            // Set the background color of the table
	
                $colour = "#c3cde6";
                foreach ($info as $row){
                    if ($row['quantity'] <= $row['remindat']) { // Needs stock
                        $colour = "#ff9999";
                    } else {
                        $colour = "#c3cde6";
                    }
                    $row['description'] = truncate($row['description'], 50);
                    $row['note'] = truncate($row['note'], 50);
            ?>
                <tr bgcolor="<?= $colour ?>">
                    <td class="center"><a href="view_item.php?id=<?= $row['id'] ?>" target="_blank"><?= $row['item'] ?></a></td>
                    <td class="center"><?= $row['quantity'] ?></td>
                    <td class="center"><?= $row['brand'] ?></td>
                    <td class="center"><?= $row['supply'] ?></td>
                    <td class="center"><?= $row['price'] ?></td>
                    <td class="center"><?= $row['description'] ?></td>
                    <td class="center"><?= $row['note'] ?></td>
                    <td class="center hide-on-print">
                        <form method='POST'>
                            <input type="submit" name="<?php echo "remove_" . $row['id'];?>" value="-"/>
                            <input name="<?php echo "quantity_" . $row['id'] . '_' . $row['quantity'];?>" type="number" style="height:25pt;width:40pt;">
                            <input type="submit" name="<?php echo "add_" . $row['id'];?>" value="+" id="add"/>
                        </form>
                    </td>
                </tr>
            <?php
                }
            ?>
            </table>
	        <?php } ?>
        </div>
    
        </form>
        <?php if (!$all) pagination($currentPage, $totalPages, "inv"); ?>
    </body>
</html>