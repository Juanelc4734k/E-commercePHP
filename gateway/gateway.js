const express = require('express');
const httpProxy = require('http-proxy');
const app = express();
const proxy = httpProxy.createProxyServer();

const servicios = {
  usuarios: 'http://localhost:8001',
  productos: 'http://localhost:8002',
  carrito: 'http.://localhost:8003',
  pedidos: 'http://localhost:8004'
};

proxy.on('error', (err, req, res) => {
  console.error('Error en el proxy:', err);
  res.status(500).send('Error en el servidor proxy');
});

Object.keys(servicios).forEach(servicio => {
  app.use(`/api/${servicio}`, (req, res) => {
    proxy.web(req, res, { target: servicios[servicio] });
  });
});

const PORT = process.env.PORT || 3000;

app.listen(PORT, () => {
  console.log(`Gateway ejecutándose en el puerto ${PORT}`);
});
