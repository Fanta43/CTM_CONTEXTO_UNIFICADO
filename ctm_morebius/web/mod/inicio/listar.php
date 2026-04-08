<?php 
//ini_set('display_errors', 'on');

// Función para obtener los datos del personal y construir un array
$personnelData = fetchPersonnelData();
$jsonData = json_encode($personnelData);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Gestión de Personal</title>
  <!-- Bootstrap CSS para estilos básicos -->
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
  <style>
    body {
      background-color: #f8f9fa;
    }
    .imagen-circular {
      width: 50px;
      height: 50px;
      border-radius: 50%;
      overflow: hidden;
      display: flex;
      justify-content: center;
      align-items: center;
      margin: auto;
    }
    .table th, .table td {
      text-align: center;
      vertical-align: middle;
    }
    .table-container {
      max-height: 70vh;
      overflow-y: auto;
    }
    .card {
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    /* Estilos para el select de opciones (cada uno diferenciado por su id único) */
    /* Se generarán estilos específicos para cada select en la función impSelectHerramienta */
  </style>
</head>
<body>
<div class="container my-4">
  <div class="card">
    <div class="card-header bg-primary text-white">
      <h3 class="mb-0">Personal</h3>
    </div>
    <div class="card-body">
      <!-- Campo de búsqueda -->
      <div class="mb-3">
        <input type="text" id="searchInput" class="form-control" placeholder="Filtro de Búsqueda" onkeyup="filterPersonnel()">
      </div>
      <!-- Contenedor de la tabla -->
      <div class="table-container">
        <table id="personnelTable" class="table table-bordered table-hover table-striped bg-white">
          <thead class="thead-dark">
            <tr>
              <th>Foto</th>
              <th>Usuario</th>
              <th>Estado</th>
              <th>Opciones</th>
            </tr>
          </thead>
          <tbody id="personnelTbody">
            <!-- Las filas se insertarán mediante JavaScript -->
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<br />

<script>
  // Se parsean los datos del personal que vienen desde PHP
  const personnelData = <?php echo $jsonData; ?>;
  // Estados para el menú de opciones
  const estados = { "26": "Resumen", "261": "Editar" };

  /**
   * Función que genera el HTML para una fila de la tabla.
   * @param {Object} record - Registro del personal.
   * @param {number} index - Índice único para el registro.
   * @returns {string} - HTML de la fila.
   */
  function buildRow(record, index) {
    return `
      <tr>
        <td>
          <div class="imagen-circular">
            <img src="${record.archivo}" alt="Imagen de ${record.nombre}" width="50" height="50">
          </div>
        </td>
        <td style="text-align: left;">
          <p style="font-size: 1.1em; font-weight: bold;">(${record.legajo}) - ${record.nombre}</p>
          <p style="font-size: 0.9em;">${record.categoria} - ${record.cargo}</p>
        </td>
        <td>
          <img src="img/ico/${record.img_estado}" alt="Estado" width="30" height="30">
        </td>
        <td>
          ${buildOptions(record.legajo, index)}
        </td>
      </tr>
    `;
  }

  /**
   * Función que encapsula la creación del select de opciones.
   * @param {string} legajo - Identificador del empleado.
   * @param {number} n - Número único para distinguir este formulario.
   * @returns {string} - HTML del select y su formulario asociado.
   */
  function buildOptions(legajo, n) {
    const variables = { legajo: legajo };
    return impSelectHerramienta(variables, estados, n);
  }

  /**
   * Función similar a la versión PHP para generar el select y el formulario.
   * Se elimina el bloque <script> interno para evitar que se muestre en pantalla.
   * @param {Object} variables - Objeto con inputs hidden a incluir.
   * @param {Object} estados - Objeto con las opciones del select.
   * @param {number} [n=0] - Número único para identificar el formulario.
   * @returns {string} - HTML completo del formulario.
   */
  function impSelectHerramienta(variables, estados, n = 0) {
    let html = `
      <style>
        /* Estilos específicos para el select identificado por su número */
        #estado${n} {
          width: 90%;
          padding: 10px;
          margin: 10px;
          font-size: 16px;
          border: 1px solid #e1e1e1;
          border-radius: 4px;
          appearance: none;
          transition: border-color 0.2s ease-in;
        }
        #estado${n}:focus {
          outline: none;
          border-color: #007BFF;
        }
      </style>
    `;
    html += `<form id="detalle_permiso${n}" name="detalle_permiso${n}" method="post" action="">`;
    // Genera los inputs hidden dinámicamente
    for (let key in variables) {
      if (variables.hasOwnProperty(key)) {
        html += `<input type="hidden" name="${key}" id="${key}" value="${variables[key]}"/>`;
      }
    }
    html += `<select name="estado${n}" id="estado${n}">
             <option value="" disabled selected>Elige una opción</option>`;
    // Genera las opciones del select dinámicamente
    for (let key in estados) {
      if (estados.hasOwnProperty(key)) {
        html += `<option value="${key}">${estados[key]}</option>`;
      }
    }
    html += `</select></form>`;
    return html;
  }

  /**
   * Función para asignar el manejador de eventos a cada select generado.
   * Se recorre cada select cuyo id comience con "estado" y se agrega el listener.
   */
  function attachSelectEventListeners() {
    const selects = document.querySelectorAll('select[id^="estado"]');
    selects.forEach(select => {
      select.addEventListener("change", function() {
        var selectedEstado = this.value;
        var n = this.id.replace("estado", "");
        var form = document.getElementById("detalle_permiso" + n);
        form.action = "index.php?estado=" + selectedEstado;
        form.submit();
      });
    });
  }

  /**
   * Función para renderizar la tabla a partir de un conjunto de datos.
   * @param {Array} data - Array de registros de personal.
   */
  function renderTable(data) {
    const tbody = document.getElementById('personnelTbody');
    const fragment = document.createDocumentFragment();
    data.forEach((record, index) => {
      const row = document.createElement('tr');
      row.innerHTML = buildRow(record, index);
      fragment.appendChild(row);
    });
    tbody.innerHTML = '';
    tbody.appendChild(fragment);
    // Una vez renderizada la tabla, asignamos los eventos a los selects generados
    attachSelectEventListeners();
  }

  /**
   * Función para filtrar los registros en base al término de búsqueda.
   */
  function filterPersonnel() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const filteredData = personnelData.filter(record => {
      return (record.legajo && record.legajo.toLowerCase().includes(searchTerm)) ||
             (record.nombre && record.nombre.toLowerCase().includes(searchTerm)) ||
             (record.categoria && record.categoria.toLowerCase().includes(searchTerm)) ||
             (record.cargo && record.cargo.toLowerCase().includes(searchTerm));
    });
    renderTable(filteredData);
  }

  // Inicialmente se renderiza la tabla con todos los registros
  document.addEventListener('DOMContentLoaded', () => {
    renderTable(personnelData);
  });
</script>

<!-- Bootstrap JS y dependencias -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>


