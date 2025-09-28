<?php
header('content-type: text/css');
?>
@import url("https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap");

*{
  margin: 0;
  padding: 0;
  box-sizing: border-box;
  font-family: "Poppins", sans-serif;
}

body{
  min-height: 100vh;
  background: url('img.jpg') no-repeat;
  background-size: cover;
  background-position: center;
  background-attachment: fixed;
  color: #fff;
}

.wrapper{
  width: 100%;
  max-width: 1200px;
  margin: 0 auto;
  background: transparent;
  border: 2px solid rgba(255, 255, 255, .2);
  color: #fff;
  border-radius: 20px;
  padding: 30px 40px;
  backdrop-filter: blur(20px);
  box-shadow: 0 0 20px rgba(0, 0, 0, .1);
}

.wrapper h1{
  font-size: 40px;
  text-align: center;
  margin-bottom: 20px;
}

.wrapper h2{
  font-size: 32px;
  text-align: center;
  margin-bottom: 20px;
}

/* Input boxes with glassmorphism */
.input-box {
  position: relative;
  width: 100%;
  height: 50px;
  margin: 20px 0;
}

.input-box input, .input-box select {
  width: 100%;
  height: 100%;
  background: transparent;
  border: none;
  outline: none;
  border: 2px solid rgba(255, 255, 255, .2);
  border-radius: 40px;
  font-size: 16px;
  color: #fff;
  padding: 20px 45px 20px 20px;
}

.input-box input::placeholder{
  color: #fff;
}

.input-box i {
  position: absolute;
  right: 20px;
  top: 50%;
  transform: translateY(-50%);
  font-size: 20px;
}

/* Buttons with glassmorphism */
.btn{
  width: 100%;
  height: 45px;
  background: #fff;
  border: none;
  outline: none;
  border-radius: 40px;
  box-shadow: 0 0 10px rgba(0, 0, 0, .1);
  cursor: pointer;
  font-size: 16px;
  color: #333;
  font-weight: 600;
  transition: all 0.3s ease;
}

.btn:hover{
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(0, 0, 0, .2);
}

.btn-custom {
  background: #fff;
  color: #333;
  transition: all 0.3s ease;
  border-radius: 40px;
  padding: 10px 20px;
  border: none;
  font-weight: 600;
}

.btn-custom:hover {
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(0, 0, 0, .2);
}

/* Navbar with glassmorphism */
.navbar {
  background: rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(20px);
  border-radius: 15px;
  margin-bottom: 20px;
  border: 2px solid rgba(255, 255, 255, .2);
}

.navbar-brand {
  color: #fff !important;
  font-weight: 700;
  font-size: 1.5rem;
}

.navbar .btn {
  background: rgba(255, 255, 255, 0.2);
  color: #fff;
  border: 1px solid rgba(255, 255, 255, .3);
  border-radius: 25px;
  padding: 8px 16px;
  transition: all 0.3s ease;
}

.navbar .btn:hover {
  background: rgba(255, 255, 255, 0.3);
  transform: translateY(-1px);
}

/* Cards with glassmorphism */
.card {
  background: rgba(255, 255, 255, 0.1);
  border: 2px solid rgba(255, 255, 255, .2);
  border-radius: 20px;
  backdrop-filter: blur(20px);
  box-shadow: 0 0 20px rgba(0, 0, 0, .1);
  transition: all 0.3s ease;
}

.card:hover {
  transform: translateY(-5px);
  box-shadow: 0 10px 30px rgba(0, 0, 0, .2);
}

/* Tables with glassmorphism */
.table {
  background: rgba(255, 255, 255, 0.1);
  border-radius: 15px;
  overflow: hidden;
  backdrop-filter: blur(20px);
}

.table thead th {
  background: rgba(255, 255, 255, 0.2);
  color: #fff;
  border: none;
  font-weight: 600;
}

.table tbody td {
  background: rgba(255, 255, 255, 0.05);
  color: #fff;
  border: none;
}

.table tbody tr:hover {
  background: rgba(255, 255, 255, 0.1);
}

/* Form elements */
.form-control, .form-select {
  background: rgba(255, 255, 255, 0.1);
  border: 2px solid rgba(255, 255, 255, .2);
  border-radius: 25px;
  color: #fff;
  backdrop-filter: blur(10px);
}

.form-control:focus, .form-select:focus {
  background: rgba(255, 255, 255, 0.15);
  border-color: rgba(255, 255, 255, .4);
  box-shadow: 0 0 10px rgba(255, 255, 255, .2);
  color: #fff;
}

.form-control::placeholder {
  color: rgba(255, 255, 255, 0.7);
}

/* Alerts with glassmorphism */
.alert {
  background: rgba(255, 255, 255, 0.1);
  border: 2px solid rgba(255, 255, 255, .2);
  border-radius: 15px;
  color: #fff;
  backdrop-filter: blur(20px);
}

.alert-success {
  background: rgba(40, 167, 69, 0.2);
  border-color: rgba(40, 167, 69, 0.3);
}

.alert-danger {
  background: rgba(220, 53, 69, 0.2);
  border-color: rgba(220, 53, 69, 0.3);
}

.alert-warning {
  background: rgba(255, 193, 7, 0.2);
  border-color: rgba(255, 193, 7, 0.3);
}

.alert-info {
  background: rgba(13, 202, 240, 0.2);
  border-color: rgba(13, 202, 240, 0.3);
}

.alert-secondary {
  background: rgba(108, 117, 125, 0.2);
  border-color: rgba(108, 117, 125, 0.3);
}

/* Form checkboxes */
.form-check-input {
  background: rgba(255, 255, 255, 0.1);
  border: 2px solid rgba(255, 255, 255, .2);
}

.form-check-input:checked {
  background: rgba(255, 255, 255, 0.3);
  border-color: rgba(255, 255, 255, .4);
}

.form-check-label {
  color: #fff;
  font-weight: 500;
}

/* Container adjustments */
.container {
  background: transparent;
}

.min-vh-100 {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* Text colors */
.text-white {
  color: #fff !important;
}

.text-center {
  text-align: center;
}
@media (max-width: 768px) {
  .wrapper {
    margin: 20px;
    padding: 20px;
  }
  
  .wrapper h1 {
    font-size: 32px;
  }
  
  .wrapper h2 {
    font-size: 28px;
  }
}
