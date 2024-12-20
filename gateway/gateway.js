const express = require('express');
const httpProxy = require('http-proxy');
const app = express();
const proxy = httpProxy.createProxyServer();

const servicios = {
  usuarios: 'http://127.0.0.1:8001',
  productos: 'http://127.0.0.1:8002',
  cartpay: 'http://127.0.0.1:8003',
  pedidos: 'http://127.0.0.1:8004',
  pagos: 'http://127.0.0.1:8005'
};

proxy.on('error', (err, req, res) => {
  console.error('Error en el proxy:', err);
  res.status(500).send('Error en el servidor proxy');
});

Object.keys(servicios).forEach(servicio => {
  app.use(`/${servicio}`, (req, res) => {
    console.log(`Redirigiendo petición a: ${servicios[servicio]}${req.url}`);
    proxy.web(req, res, { target: servicios[servicio] });
  });
});

const PORT = process.env.PORT || 3000;

app.listen(PORT, () => {
  console.log(`Gateway ejecutándose en el puerto ${PORT}`);
});
