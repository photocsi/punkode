<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>

<body>

  <?php   
    require_once '../setup.php';
    require_once 'header.php';
    require_once 'offcanvas.html'; 

    use Punkode as pk; ?>
    <main>
        <div class="container-fluid text-center" style="padding: 2rem">
            <div class="row align-items-start">
                <div class="col-10" style="text-align: left;  padding-right: 3rem ">
                    <h5><b style="color: #164d09">TABLE_PK</b></h5>
                    </br>
                    <p>Crea una tabella già impaginata con tutte le funzionalità di modifica, aggiungi ed elimina campo i più la possibilita di modificare le colonne del db,
                         in futuro sarà possibile scegliere anche lo stile della tabella.</p>
                    </br>
                   
                    </br>
                    <p>Instanziare la classe<b class="text-primary"> $table = new TABLE_PK</b> </p>
                    <p>Richiamare la funzione<b class="text-primary"> $table->template_classic_pk</b>(
                        <i class="text-secondary">string</i><b class="text-success"> $table</b> ,
                        <i class="text-secondary">string</i><b class="text-success"> $element</b> ,
                        <i class="text-secondary">string</i><b class="text-success"> $exclude</b> ,
                        <i class="text-secondary">string</i><b class="text-success" > $action</b> )  </p>
                 
                    <h5><b>Parametri</b></h5>
                   
                   <p> <i class="text-secondary">string</i> <b class="text-success">$table</b><i> (obbligatorio)</i> </br> Nome della tabella da mostrare</p>

                   <p> <i class="text-secondary">string</i> <b class="text-success">$element</b><i> (opzionale)</i> </br> 3 parametri possibili: <b> '' , 'edit' , 'all'</b> </br>
                    <ul>
                     <li><b>''</b> vuoto per visualizzare solo la tabella senza pulsanti. </br> </li>
                     <li> <b>'edit'</b> per mostrare i pultanti di aggiungi record e modifica o elimina record.  </br> </li>
                     <li> <b>'all'</b> per mostrare tutte le funzioni di editing dei record e di modifica della tabella aggiungere o eliminare le colonne della tabella. </br> </li>
                     <li>  Per impostazione definitiva è impostato su <b>'all'</b>.</p></li></ul>

                    <p> <i class="text-secondary">array</i> <b class="text-success">$exclude</b><i> (opzionale)</i> </br> la lista di colonne che si vogliono escludere dalla visualizzazione</p>

                    <p> <i class="text-secondary">string</i> <b class="text-success">$action</b><i> (opzionale)</i> </br> La pagina in cui indirizzare l'utente dopo l'utilizzo di un pulsante capiterà raramente, per impostazione predefinita
                e impostata sulla stessa pagina '#' , ricordiamo di inserire all'inizio della pagina la funzione  di php <b>ob_start();</b> questo eviterà il rinvio dei dati nel caso in cui si ricarichi la pagina</p>
                  
                  
                    <h5><b>Considerazioni</b></h5>
                    </br>
                    <p>Indubbiamente per ora lo strumento più potente di PunKode con una sola riga di codice avete a disposizione tutti gli strumenti necessari per lavorare su una tabella del database
                        , tabella responsive da posizionare nel vostro sito dove volete, da utilizzare come un gestionale già pronto e con le misure di sicurezza come sanitizzazione , validazione che verranno ampliate sempre di più
                        per rendere la vostra tabella sempre meno attaccabile.

                    </p>
                    
                    <h5><b>Esempi</b></h5>

                    <div class="card text-start">
                        <div class="card-header">
                            C'e' poco da spiegare, basta instaziare la classe e come parametro inserire il nome della tabella da visualizzare
                        </div>
                        <div class="card-body">
                            <p class="card-text">
                           <b> $table = new TABLE_PK </br>
                                $table->template_classic_pk('anni');</b>
                            </p>
                        </div>
                        <div class="card-footer text-body-secondary">
                            <?php $input = new pk\TABLE_PK();
                            $input->int_pk('anni');
                            ?>
                        </div>
                    </div>
                    <br>
                

                    <br>
                   


                </div>
               
            </div>
            </div>


            </div>
            </div> </div>



       
       
        <!-- ultimo div che chiude la side bar, da aggiungere al fondo di ogni pagina -->
    </main>



</body>

</html>