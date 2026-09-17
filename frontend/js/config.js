// El frontend prueba los backends en este orden de prioridad:
// 1. PHP (XAMPP / Apache)
// 2. Java (Spring Boot :8080)
// 3. Python (Flask :5000)
const API_CANDIDATES = [
  'http://localhost/LicoFast1/backend-php/api',
  'http://localhost/LicoFast/backend-php/api',
  'http://localhost:8080/api',
  'http://localhost:5000/api'
];

// Backend seleccionado actualmente (persistido en localStorage)
let API_BASE = localStorage.getItem('lf_backend') || API_CANDIDATES[0];

