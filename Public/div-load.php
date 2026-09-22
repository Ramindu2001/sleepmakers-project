
<style>
.loader {
    top: 0;
    background: #fff;
    padding: 0;
    margin: 0;
    height: 100%;
    width: 100%;
    display: grid;
    place-items: center;
    position: fixed;
    z-index: 10000000;
}

.rots {
    font-size: 185px !important;
    color: black;
}

/* Define infinite rotation animation */
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Apply animation when the class is added */
.rotate {
    animation: spin 1s linear infinite;
}
</style>

<div class="loader" id="loader">
    <i class="ti ti-refresh rotate rots"></i>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
  $(document).ready(function(){
    // $('#loader').delay(800).fadeOut(800);
  });
</script>
