<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
        <link rel="icon" type="image/png" href="images/logo.png">
    <link rel="stylesheet" href="style.css?v=1.1">
</head>
<body>

 <nav class="navbar">
        <div class="logo">
            <a href="admin.php"><img src="images/logo.png" alt="Coffee Shop Logo">
                <span class="logo-text">Admin Dashboard</span></a>
        </div>
        <ul class="nav-links">
            <li><a href="user_data.php">USERS</a></li>
            <li><a href="stock.php">STOCK</a></li>
            <li><a href="orders.php">ORDERS</a></li>
        </ul>
        <button class="cart-button">
            <img src="images/logout.png" alt="Logout Icon"> 
            <a href="logout.php" class="logout-btn">Logout</a>
        </button>
    </nav>

    <div class="wlc-container">
        <h1 id="typewriter-text"></h1>
    </div>
</body>
<script>
    // typewriter effect for admin dashboard welcome message
const text = "Welcome, Admin!";
let index = 0;
let isDeleting = false;

const typeSpeed = 200;  // Speed while typing (ms)
const eraseSpeed = 50; // Speed while erasing (ms)
const pauseTime = 2000; // Pause when word is full before erasing (ms)

function typeWriter() {
  const target = document.getElementById("typewriter-text");

  if (isDeleting) {
    // Remove a character
    target.textContent = text.substring(0, index - 1);
    index--;
  } else {
    // Add a character
    target.textContent = text.substring(0, index + 1);
    index++;
  }

  // Set default speed based on state
  let currentDelay = isDeleting ? eraseSpeed : typeSpeed;

  // Word is fully typed -> pause, then start deleting
  if (!isDeleting && index === text.length) {
    currentDelay = pauseTime;
    isDeleting = true;
  } 
  // Word is fully erased -> pause briefly, then start typing again
  else if (isDeleting && index === 0) {
    isDeleting = false;
    currentDelay = 500;
  }

  setTimeout(typeWriter, currentDelay);
}

window.onload = typeWriter;
</script>
</html>
