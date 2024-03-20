
document.addEventListener('activeElement', updatePK('come','testo','id_come','23'));

function add($label,$width,$name){
    var label = document.getElementById($label).value;
    var width = document.getElementById($width).value;
    var name = document.getElementById($name).value;
    
    var code = document.getElementById('code');
    var text ='$db_class->input_number("' + label + '","'+ name +'", array(' + width + ', "s"));';
   
    code.innerHTML += text; 
    code.innerHTML += '</br>';
   
}

function updatePK(table,name,where,value){
 var valore = document.getElementById(name+value).value;
 if (valore != 1){
  valore = 1;
 }else{
  valore = 0;
 }
    fetch('punkode/includes/ajax-class.php', {
        method: 'post',
        headers: {
         'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: "table="+table+ "&"+ "name=" + name +"&" + "value=" + valore + "&" + "where=" + where + "&" + "value_where=" + value
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

    /*   var colorButton = document.getElementById('button' + name + value);
          colorButton.className = "btn btn-outline-success"; */
    } 


    function reload(){
      window.location.reload()
    }