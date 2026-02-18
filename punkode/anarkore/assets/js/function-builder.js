


document.addEventListener('click', function () {

  const id_div = document.activeElement.id;
  const arrayValori = id_div.split(',');
  const type = arrayValori[0];
  if (arrayValori[0] == 'text' || arrayValori[0] == 'password' || arrayValori[0] == 'textarea') {
    document.getElementById(id_div).addEventListener("keyup", function () {
      const value = document.getElementById(id_div).value;
      const table = arrayValori[1];
      const where = arrayValori[2];
      const id = arrayValori[3];
      const name = arrayValori[4];
      const urlMain = arrayValori[5];
      console.log(id, name, value, where, table, urlMain);

      updatePK(type, table, name, value, where, id, urlMain, id_div);
    });
  } else if (arrayValori[0] == 'date' || arrayValori[0] == 'number' || arrayValori[0] == 'option') {
    document.getElementById(id_div).addEventListener("change", function () {
      const value = document.getElementById(id_div).value;
      const table = arrayValori[1];
      const where = arrayValori[2];
      const id = arrayValori[3];
      const name = arrayValori[4];
      const urlMain = arrayValori[5];
      console.log(id, name, value, where, table, urlMain);

      updatePK(type, table, name, value, where, id, urlMain, id_div);
    });
  }else if (arrayValori[0] == 'email') {
    document.getElementById(id_div).addEventListener("blur", function () {
      const value = document.getElementById(id_div).value;
      const table = arrayValori[1];
      const where = arrayValori[2];
      const id = arrayValori[3];
      const name = arrayValori[4];
      const urlMain = arrayValori[5];
      console.log(id, name, value, where, table, urlMain);
      updatePK(type, table, name, value, where, id, urlMain, id_div);
    });
  } else if (arrayValori[0] == 'check') {
    const value = document.getElementById(id_div).value;

    const table = arrayValori[1];
    const where = arrayValori[2];
    const id = arrayValori[3];
    const name = arrayValori[4];
    const urlMain = arrayValori[5];
    updatePK(type, table, name, value, where, id, urlMain);

  }
  /* creare un'altro if per la password tocca creare anche un'altra pagina php per la funzione che prima cripti la password */

});



function updatePK(type, table, name, value, where, id, urlMain, id_div) {

  const urlDest = urlMain + 'punkode/anarkore/includes/ajax/update_ajax.php';  /* scoprire come recuperare questo url */
   fetch(urlDest, {
    method: 'post',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded'
    },
    body: "type=" + type + "&" + "table=" + table + "&" + "name=" + name + "&" + "value=" + value + "&" + "where=" + where + "&" + "value_where=" + id
  })
    .then(
      function (response) {
        if (response.status !== 200) {
          console.log('Looks like there was a problem. Status Code: ' +
            response.status);
          return;
        }
        // Examine the text in the response
        response.text().then(function (data) {

          setTimeout(colorFunc, 800);
          setTimeout(nocolorFunc, 900);
          setTimeout(colorFunc, 1000);
          setTimeout(nocolorFunc, 1800);

          function colorFunc() {
            document.getElementById(id_div).style.background = '#cdfdca';
          }
          function nocolorFunc() {
            document.getElementById(id_div).style.background = ''
          }

        });
      }

    )
    .catch(function (err) {
      console.log('Fetch Error :-S', err);
    });
 
}

function insertPK(type, table, name, value, where, id, urlMain, id_div) {

  const urlDest = urlMain + 'punkode/includes/ajax/update_ajax.php';  /* scoprire come recuperare questo url */
  fetch(urlDest, {
    method: 'post',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded'
    },
    body: "type=" + type + "&" + "table=" + table + "&" + "name=" + name + "&" + "value=" + value + "&" + "where=" + where + "&" + "value_where=" + id
  })
    .then(
      function (response) {
        if (response.status !== 200) {
          console.log('Looks like there was a problem. Status Code: ' +
            response.status);
          return;
        }
        // Examine the text in the response
        response.text().then(function (data) {

          setTimeout(colorFunc, 800);
          setTimeout(nocolorFunc, 900);
          setTimeout(colorFunc, 1000);
          setTimeout(nocolorFunc, 1800);

          function colorFunc() {
            document.getElementById(id_div).style.background = '#cdfdca';
          }
          function nocolorFunc() {
            document.getElementById(id_div).style.background = ''
          }

        });
      }

    )
    .catch(function (err) {
      console.log('Fetch Error :-S', err);
    });

}

/*  function addrecord(table, urlMain){
   const urlDest =urlMain + 'punkode/includes/ajax/add_record_ajax.php';  // scoprire come recuperare questo url 
   fetch(urlDest, {
     method: 'post',
     headers: {
      'Content-Type': 'application/x-www-form-urlencoded'
     },
     body: "table="+table
   })
   .then(
     function(response) {
       if (response.status !== 200) {
         console.log('Looks like there was a problem. Status Code: ' +
           response.status);
         return;
       }
       // Examine the text in the response
       response.text().then(function(data) {
         console.log(data);
       });
     }

   )
   .catch(function(err) {
     console.log('Fetch Error :-S', err);
   });
 } */

function mostraPass(id) {
  var input = document.getElementById(id);
  console.log(input.type);
  if (input.type === "password") {
    input.type = "text";
  } else {
    input.type = "password";
  }
}

