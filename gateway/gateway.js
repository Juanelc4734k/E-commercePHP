const express = require('express');
const httpProxy = require('http-proxy');
const app = express();
const proxy = httpProxy.createProxyServer();

// Configuración del proxy
const servicios = {
  usuarios: 'http://localhost:3001',
  productos: 'http://localhost:3002',
  pedidos: 'http://localhost:3003'
};

// Middleware para manejar errores del proxy
proxy.on('error', (err, req, res) => {
  console.error('Error en el proxy:', err);
  res.status(500).send('Error en el servidor proxy');
});

// Rutas para redireccionar las peticiones
Object.keys(servicios).forEach(servicio => {
  app.use(`/api/${servicio}`, (req, res) => {
    proxy.web(req, res, { target: servicios[servicio] });
  });
});

// Puerto del gateway
const PORT = process.env.PORT || 3000;

app.listen(PORT, () => {
  console.log(`Gateway ejecutándose en el puerto ${PORT}`);
});
