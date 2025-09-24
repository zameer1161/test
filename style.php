<?php
header('content-type: text/css');
?>
  <style>

    
:root {
  --bg-color: #0d0d0d;
  --text-color: #f2f2f2;
  --navbar-bg: #1a1a1a;
  --card-bg: #1a1a1a;
  --btn-bg: #ff9900;
  --btn-hover: #e68a00;
}

body.light {
  --bg-color: #ffffff;
  --text-color: #222;
  --navbar-bg: #f0f0f0;
  --card-bg: #ffffff;
  --btn-bg: #007bff;
  --btn-hover: #0056b3;
}


body {
  margin: 0;
  font-family: 'Roboto', sans-serif;
  background-color: var(--bg-color);
  color: var(--text-color);
  line-height: 1.6;
}


.navbar {
  background: var(--navbar-bg);
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 20px;
  position: sticky;
  top: 0;
  z-index: 1000;
}

.navbar a {
  color: var(--text-color);
  text-decoration: none;
  margin: 0 12px;
  font-weight: 500;
  transition: color 0.3s;
}

.navbar a:hover {
  color: #ff9900;
}

.btn {
  background: var(--btn-bg);
  border: none;
  padding: 10px 18px;
  color: #000000ff;
  font-weight: bold;
  border-radius: 4px;
  cursor: pointer;
  transition: 0.3s;
}

.btn:hover {
  background: var(--btn-hover);
}


.card {
  background: var(--card-bg);
  border-radius: 8px;
  margin: 15px;
  padding: 15px;
  box-shadow: 0 2px 6px rgba(0,0,0,0.6);
  transition: transform 0.2s;
}

.card:hover {
  transform: scale(1.02);
}
  
    
    .navbar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      color:#f0f0f0;
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
