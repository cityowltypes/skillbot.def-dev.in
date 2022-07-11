<?php
namespace Theme;


class PdfForm
{
    /*
    * Path to raw PDF form
    * @var string
    */
    private string $pdfurl;

    /*
    * Form data
    * @var array
    */
    private array $data;

    /*
    * Path to filled PDF form
    * @var string
    */
    private string $output;

    /*
    * Flag for flattening the file
    * @var string
    */
    private string $flatten;

    // ...

    public function __construct(string $pdfurl, array $data)
    {
        $this->pdfurl = $pdfurl;
        $this->data   = $data;
    }

    private function tmpfile()
    {
        return tempnam(sys_get_temp_dir(), gethostname());
    }

    // to print all fields on screen for dev debugging
    public function fields($pretty = false)
    {
        $tmp = $this->tmpfile();

        exec("pdftk {$this->pdfurl} dump_data_fields > {$tmp}");
        $con = file_get_contents($tmp);

        unlink($tmp);
        return $pretty == true ? nl2br($con) : $con;
    }

    public function makeFdf(array $data)
    {
        $fdf = '%FDF-1.2
        1 0 obj<</FDF<< /Fields[';

        foreach ($data as $key => $value) {
            $fdf .= '<</T(' . $key . ')/V(' . $value . ')>>';
        }

        $fdf .= "] >> >>
        endobj
        trailer
        <</Root 1 0 R>>
        %%EOF";

        $fdf_file = $this->tmpfile();
        file_put_contents($fdf_file, $fdf);

        return $fdf_file;
    }

    public function flatten()
    {
        $this->flatten = ' flatten';
        return $this;
    }

    private function generate()
    {

        $fdf = $this->makeFdf($this->data);
        $this->output = $this->tmpfile();
        exec("pdftk {$this->pdfurl} fill_form {$fdf} output {$this->output}{$this->flatten}");

        unlink($fdf);
    }

    public function save(string $path = null)
    {
        if (is_null($path)) {
            return $this;
        }

        if (!isset($this->output)) {
            $this->generate();
        }

        $dest = pathinfo($path, PATHINFO_DIRNAME);
        if (!file_exists($dest)) {
            mkdir($dest, 0775, true);
        }

        copy($this->output, $path);
        unlink($this->output);

        $this->output = $path;

        return $this;
    }

    public function download()
    {
        if (!$this->output) {
            $this->generate();
        }

        $filepath = $this->output;
        if (file_exists($filepath)) {

            header('Content-Description: File Transfer');
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename=certificate.pdf');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');

            readfile($filepath);
            unlink($filepath);
        }
    }
}