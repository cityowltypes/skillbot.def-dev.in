<?php
/**
 * @var string $type
 * @var string $slug
 */
?>

<script src="/node_modules/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/numeral.js/2.0.6/numeral.min.js"></script>
<!-- chart js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.8.0/dist/chart.min.js"></script>
<!-- autocolors for chartjs -->
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-autocolors"></script>
<!-- chartjs labels -->
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
<!-- custom js -->
<script src="/theme/assets/js/custom.js"></script>

<?php
if ($type === 'report') {
    echo "<script src='/theme/assets/js/analytics.js'></script>";
}
?>

</body>
</html>
