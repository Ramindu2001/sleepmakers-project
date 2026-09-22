<style>
          .loader
        {
            top: 0;
            background: #fff;
            padding: 0;
            margin: 0;
            height: 100vh;
            width: 100%;
            display: grid;
            place-items: center center;
            position: fixed;
            z-index: 10000000;
        }
.containers {
  border: 16px solid #f3f3f3;
  border-radius: 50%;
  border-top: 16px solid #506fd9;
  border-bottom: 16px solid #506fd9;
  width: 120px;
  height: 120px;
  -webkit-animation: spin 1s linear infinite; /* Safari */
  animation: spin 1s linear infinite;
}

/* Safari */
@-webkit-keyframes spin {
  0% { -webkit-transform: rotate(0deg); }
  100% { -webkit-transform: rotate(360deg); }
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}
.loader .w-10
{
  width:40% !important;
}
.loader .text-center
{
    width: 50%;
    margin-top: -170px;
    text-align: center !important;
}
</style>
<div class="loader" id="loader">
  <img class="w-10" src="../assets/img/new logo.png" alt="">
  <p class="text-center">Success is not the key to happiness. Happiness is the key to success. If you love what you are doing, you will be successful</p>
  <div class="containers">
  </div>
</div>
<script>
  $(document).ready(function(){
    $('#loader').delay(800).fadeOut(800);
  });
</script>