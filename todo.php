<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>To-Do List</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
    /* Added styling for the form for better user experience */
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
            color: #333; 
        }
        .container {
            width: 100%;
            max-width: 800px;
            margin: auto;
            padding: 20px;
            background-color: #fff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            border-radius: 5px;
            margin-top: 40px;
        }
        .input-field {
            font-size: 18px; 
            padding: 15px; 
            margin: 10px 0; 
            border: 1px solid #ccc; 
            border-radius: 4px; 
            box-sizing: border-box; 
            width: 100%; 
            max-width: 400px; 
            display: block; 
            margin-left: auto; 
            margin-right: auto;
          }
            
        h2 {
            color: #444;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }
        th {
            background-color: #f8f8f8;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        .button {
            display: inline-block;
            padding: 8px 15px;
            font-size: 16px;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            outline: none;
            color: #fff;
            background-color: #4CAF50;
            border: none;
            border-radius: 5px;
            box-shadow: 0 6px #999;
            width: 200px;
            margin-top: 10px;
        }
        .button:hover {background-color: #3e8e41}
        .button:active {
            background-color: #3e8e41;
            box-shadow: 0 5px #666;
            transform: translateY(4px);
        }
       .done-btn, .delete-btn {
            margin-left: 10px; 
            padding: 5px 10px; 
            border: none; 
            border-radius: 4px; 
            cursor: pointer; 
   
        }

        .done-btn:hover {
           background-color: #4CAF50; 
           color: white; 
        } 

        .delete-btn:hover {
           background-color: #f44336; 
           color: white; 
        }

        .input-field {
            padding: 10px;
            margin: 5px 0;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            width: calc(100% - 22px);
        }
        form {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>


<?php
// Include database credentials and connect to the database
require 'secrets.php';

$mysqli = new mysqli("localhost", DB_UID, DB_PWD, "example_db");

// Check connection
if ($mysqli === false) {
    die("ERROR: Could not connect. " . $mysqli->connect_error);
}

echo "Connected Successfully. Host info: " . $mysqli->host_info;

// Check if the 'Show Completed' toggle has been activated
$showCompleted = false;
if (isset($_POST['toggle_completed'])) {
    $showCompleted = $_POST['toggle_completed'] == 'show';
}

// Handle POST requests for marking tasks as completed or adding new tasks
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update task to mark as completed
    if (isset($_POST['id'])) {
        $id = $_POST['id'];
        $sqlUpdate = "UPDATE todos SET completed = 1 WHERE id = ?";
        
        if ($stmt = $mysqli->prepare($sqlUpdate)) {
            $stmt->bind_param("i", $id);
            $stmt->execute();
            echo "Task marked as completed.";
        } else {
            echo "ERROR: Could not prepare query: $sqlUpdate. " . $mysqli->error;
        }
        // Insert a new task
    } elseif (isset($_POST['title'])) {
        $new_title = $_POST['title'];
        $sqlInsert = "INSERT INTO todos(title) VALUES (?)";
        
        if ($stmt = $mysqli->prepare($sqlInsert)) {
            $stmt->bind_param("s", $new_title);
            $stmt->execute();
            echo "Records inserted successfully.";
        } else {
            echo "ERROR: Could not prepare query: $sqlInsert. " . $mysqli->error;
        }
    } elseif (isset($_POST['delete_id'])) { // Deletion block
        $delete_id = $_POST['delete_id'];
        $sqlDelete = "DELETE FROM todos WHERE id = ?";
        
        if ($stmt = $mysqli->prepare($sqlDelete)) {
            $stmt->bind_param("i", $delete_id);
            $stmt->execute();
            echo "Task deleted successfully.";
        } else {
            echo "ERROR: Could not prepare query: $sqlDelete. " . $mysqli->error;
        }
    }
}
// Prepare SQL query based on whether completed tasks should be shown
$sql = $showCompleted
? "SELECT id, title, created, completed FROM todos ORDER BY created DESC"
    : "SELECT id, title, created, completed FROM todos WHERE completed = 0 ORDER BY created DESC";
    
    ?>

<div class="container">
<h2>To-do list items</h2>
<table>
    <tbody>
        <tr>
            <th>Item</th>
            <th>Added on</th>
            <th>Complete</th>
        </tr>
        
        <?php
        
        // Fetch tasks from database and display them
        if ($result = $mysqli->query($sql)) {
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_array()) {
                    echo "<tr>";
                    // Display task title, crossed out if completed
                    $titleDisplay = $row['completed'] ? "<del>" . htmlspecialchars($row['title']) . "</del>" : htmlspecialchars($row['title']);
                    echo "<td>" . $titleDisplay . "</td>";
                    echo "<td>" . $row['created'] . "</td>";
                    // Display 'Done' button for incomplete tasks
                    if (!$row['completed']) {
                        echo '<td><form method="post" action="todo.php">';
                        echo "<input type='hidden' name='id' value='" . $row['id'] . "'>";
                        echo "<button type='submit' class='done-btn'>Done</button>"; // Added class here
                        echo "</form></td>";
                    } else {
                        // Indicate task is completed
                        echo "<td>Completed";
                        echo '<form method="post" action="todo.php" style="display: inline;">';
                        echo "<input type='hidden' name='delete_id' value='" . $row['id'] . "'>";
                        echo "<button type='submit' class='delete-btn'>Delete</button>"; // Added class here
                        echo "</form>";
                    }
                    echo "</tr>";
                }
                echo "</tbody></table>";
                $result->free();
            } else {
                echo "The to-do list is currently empty.</tbody></table>";
            }
        } else {
            echo "ERROR: Could not execute $sql. " . $mysqli->error . "</tbody></table>";
        }
        ?>

<!-- Submit button for adding a new to-do item -->
<form method="post" action="todo.php">
    <input type="text" name="title" placeholder="To-do item" required class="input-field">
    <button type="submit" class="button">Submit</button>
</form>

<!-- Toggle completed items visibility -->
<form method="post" action="todo.php">
    <input type="hidden" name="toggle_completed" value="<?php echo $showCompleted ? 'hide' : 'show'; ?>">
    <button type="submit" class="button"><?php echo $showCompleted ? 'Hide completed' : 'Show completed'; ?></button>
</form>

</div>
</body>
</html>
