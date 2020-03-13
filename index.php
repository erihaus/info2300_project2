<?php include("includes/init.php");
$title = "Home";
$db = open_sqlite_db("secure/catalog.sqlite");
$messages = array();

function loop($values)
{
  foreach ($values as $value) {
    echo "<option value=\"" . htmlspecialchars($value) . "\">" . htmlspecialchars($value) . "</option>";
  }
}
function print_record($record)
{
?>
  <tr>
    <td><?php echo htmlspecialchars($record["name"]); ?></td>
    <td>
      <a href="<?php
                echo htmlspecialchars($record["link"]); ?>"><?php
                                                            echo htmlspecialchars($record["link"]); ?></a>
    </td>
    <td><?php echo htmlspecialchars($record["category"]); ?></td>
    <td><?php echo htmlspecialchars($record["commitment"]); ?></td>
    <td><?php echo htmlspecialchars($record["frequency"]); ?></td>
    <td><?php echo htmlspecialchars($record["application"]); ?></td>
  </tr>
<?php
}

const SEARCH_FIELDS = [
  "all" => "Search Everything",
  "name" => "Search Name",
  "category" => "Search Category",
  "commitment" => "Search Commitment",
  "frequency" => "Search Frequency",
];

if (isset($_GET['search'])) {
  $do_search = TRUE;

  // check if the category exists in the SEARCH_FIELDS array
  // This "filter input" protects us from SQL injection for fields
  $category = filter_input(INPUT_GET, 'category', FILTER_SANITIZE_STRING);
  if (in_array($category, array_keys(SEARCH_FIELDS))) {
    $search_field = $category;
  } else {
    array_push($messages, "Invalid category for search.");
    $do_search = FALSE;
  }

  // Get the search terms
  $search = filter_input(INPUT_GET, 'search', FILTER_SANITIZE_STRING);
  $search = trim($search);
} else {
  // No search provided, so set the product to query to NULL
  $do_search = FALSE;
  $category = NULL;
  $search = NULL;
}



// Insert Form

// Get the list of clubs from the database.
$categories = exec_sql_query($db, "SELECT DISTINCT category FROM clubs", NULL)->fetchAll(PDO::FETCH_COLUMN);
$applications = exec_sql_query($db, "SELECT DISTINCT application FROM clubs", NULL)->fetchAll(PDO::FETCH_COLUMN);
$frequencies = exec_sql_query($db, "SELECT DISTINCT frequency FROM clubs", NULL)->fetchAll(PDO::FETCH_COLUMN);
$names = exec_sql_query($db, "SELECT name FROM clubs", NULL)->fetchAll(PDO::FETCH_COLUMN);
$links = exec_sql_query($db, "SELECT link FROM clubs", NULL)->fetchAll(PDO::FETCH_COLUMN);
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $valid_review = TRUE;

  $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
  $link = filter_input(INPUT_POST, 'link', FILTER_SANITIZE_STRING);
  $category = filter_input(INPUT_POST, 'category', FILTER_SANITIZE_STRING);
  $commitment = filter_input(INPUT_POST, 'commitment', FILTER_VALIDATE_INT);
  $frequency = filter_input(INPUT_POST, 'frequency', FILTER_SANITIZE_STRING);
  $application = filter_input(INPUT_POST, 'application', FILTER_SANITIZE_STRING);

  // rating required
  if ($commitment < 0) {
    $valid_review = FALSE;
  }
  if (in_array($name, $names) || in_array($link, $links)) {
    $valid_review = FALSE;
  }

  if (
    !in_array($category, $categories) || !in_array($application, $applications) || !in_array($frequency, $frequencies)
  ) {
    $valid_review = FALSE;
  }




  // insert valid reviews into database
  if ($valid_review) {
    // TODO: query to insert new record
    $sql = "INSERT INTO clubs (name, link, category, commitment, frequency, application) VALUES (:name, :link, :category, :commitment, :frequency, :application)";
    $params = array(
      ':name' => $name,
      ':link' => $link,
      ':category' => $category,
      ':commitment' => $commitment,
      ':frequency' => $frequency,
      ':application' => $application
    );
    $result = exec_sql_query($db, $sql, $params);
    if ($result) {
      array_push($messages, "Your club has been added to our database. Thank you!");
    } else {
      array_push($messages, "Failed to add club.");
    }
  } else {
    array_push($messages, "Failed to add club. All fields besides link are required. Club with same name or link might already be in database. Commitment must be a positive integer.");
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />

  <title>Cornell Undergrad Orgs</title>
  <link rel="stylesheet" href="styles/all.css">
</head>

<body>
  <?php include("includes/header.php"); ?>
  <div id="main">
    <?php
    // Write out any messages to the user.
    foreach ($messages as $message) {
      echo "<p><strong>" . htmlspecialchars($message) . "</strong></p>\n";
    }

    ?>

    <form id="searchForm" action="index.php" method="get" novalidate>
      <select name="category">
        <?php foreach (SEARCH_FIELDS as $field_name => $label) { ?>
          <option value="<?php echo htmlspecialchars($field_name); ?>"><?php echo htmlspecialchars($label); ?></option>
        <?php } ?>
      </select>
      <input type="text" name="search" required />
      <button type="submit">Search</button>
    </form>


    <?php
    if ($do_search) { // We have a specific shoe to query!
    ?>
      <h2>Search Results</h2>

      <?php
      if ($search_field == "all") {
        // Search across all fields at once!

        // TODO: query to search across all fields at once. Hint: You need logical operators
        $sql = "SELECT * FROM clubs WHERE ( name LIKE '%' || :search || '%') OR (category LIKE '%' || :search || '%') OR (commitment LIKE '%' || :search || '%') OR (frequency LIKE '%' || :search || '%')";
        $params = array(
          ':search' => $search
        );
      } else {
        // Search across the specified field

        // TODO: query to search the $search_field ONLY for $search
        $sql = "SELECT * FROM clubs WHERE ( $search_field LIKE '%' || :search || '%')";
        $params = array(
          ':search' => $search
        );
      }
    } else {
      // No shoe to query, so return everything!
      // Hint: You don't need to change any of this code.
      ?>
      <h2>All Clubs and Organizations</h2>
      <?php

      $sql = "SELECT * FROM clubs";
      $params = array();
    }
    // Get the shoes to display
    // Hint: You don't need to change any of this code.
    $result = exec_sql_query($db, $sql, $params);
    if ($result) {
      // The query was successful, let's get the records.
      $records = $result->fetchAll();

      if (count($records) > 0) {
        // We have records to display
      ?>
        <table id="clubs">
          <tr>
            <th>Name</th>
            <th>Website URL</th>
            <th>Category</th>
            <th>Commitment (Hours/Week)</th>
            <th>Frequency of Meetings</th>
            <th>Application?</th>
          </tr>

          <?php
          foreach ($records as $record) {
            print_record($record);
          }
          ?>
        </table>
    <?php
      } else {
        // No results found
        echo "<p>No matching clubs found.</p>";
      }
    }
    ?>
  </div>
  <div id="submit">
    <h2>Submit your Club</h2>

    <form action="index.php" method="post" novalidate>
      <div>
        <label>Name of Club or Organization:</label>
        <input type="text" name="name" />
      </div>
      <div>
        <label>Website URL: </label>
        <input type="text" name="link" />
      </div>

      <div>
        <label>Category:</label>
        <select name="category" required>
          <option value="" selected disabled>Choose Category</option>
          <?php
          loop($categories);
          ?>
        </select>
      </div>

      <div>
        <label>Time Commitment (Hours per week) </label>
        <input type="number" name="commitment" />
      </div>

      <div>
        <label>Frequency of Meetings:</label>
        <select name="frequency" required>
          <option value="" selected disabled>Choose Frequency</option>
          <?php
          loop($frequencies);
          ?>
        </select>
      </div>
      <div>
        <label>Application required?</label>
        <select name="application" required>
          <option value="" selected disabled>Choose response</option>
          <?php
          loop($applications);
          ?>
        </select>
      </div>

      <div>
        <span>
          <!-- empty element; used to align submit button --></span>
        <button id="add" type="submit">Add Club</button>
      </div>
    </form>
  </div>

  <?php include("includes/footer.php"); ?>

</body>

</html>
