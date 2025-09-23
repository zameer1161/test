<?php
header('content-type: text/css');
?>
  <style>
    body {
      margin: 0;
      font-family: 'Roboto', sans-serif;
      background: #0d0d0d;
      color: #fff;
    }

    /* ===== Navbar ===== */
    .navbar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      background: #1a1a1a;
      padding: 10px 20px;
      position: sticky;
      top: 0;
      z-index: 1000;
    }
    .logo {
      font-size: 1.8rem;
      font-weight: 700;
    }
    .logo span {
      color: #ff9900;
    }
    .search-box {
      flex: 1;
      margin: 0 20px;
    }
    .search-box input {
      width: 100%;
      padding: 8px;
      border: none;
      border-radius: 4px;
      outline: none;
    }
    .nav-actions {
      display: flex;
      gap: 10px;
    }
    .btn {
      background: #ff9900;
      border: none;
      padding: 8px 14px;
      color: #fff;
      font-weight: 600;
      border-radius: 4px;
      cursor: pointer;
      transition: background 0.2s ease;
    }
    .btn:hover {
      background: #e68a00;
    }

    /* ===== Grid Layout ===== */
    .content {
      padding: 20px;
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 15px;
    }
    .card {
      background: #1a1a1a;
      border-radius: 6px;
      overflow: hidden;
      transition: transform 0.2s ease;
    }
    .card img {
      width: 100%;
      display: block;
    }
    .card:hover {
      transform: scale(1.03);
    }
    .card-title {
      padding: 10px;
      font-size: 1rem;
    }
