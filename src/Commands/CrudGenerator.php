<?php

namespace Dscheff\CrudGenerator\Commands;

use Illuminate\Support\Str;

/**
 * Class CrudGenerator.
 *
 * @author Daniel Scheff <scheff-code@gmail.com>
 * @author  Awais <asargodha@gmail.com>
 */
class CrudGenerator extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:crud
                            {name : Table name}
                            {--route= : Custom route name}
                            {--title= : Visible model name}';

    // {--model-name= : Model name if not studly table name}

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create bootstrap CRUD operations';

    /**
     * The stubs to be generated.
     *
     * @var array
     */
//    protected $stubs = ['index', 'create', 'edit', 'form', 'show', '_index', '_create', '_edit'];
    protected $stubs = ['index', 'create', 'edit', 'form', 'show'];

    /**
     * Execute the console command.
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     *
     * @return bool|null
     */
    public function handle()
    {
        $this->info('Running Crud Generator ...');

        $this->table = $this->getNameInput();

        // If table not exist in DB return
        if (!$this->tableExists()) {
            $this->error("`{$this->table}` table not exist");

            return false;
        }

        // Build the class name from table name
        $this->name = $this->_buildClassName();

        // Generate the crud
        $this->buildOptions()
            ->buildController()
            ->buildModel()
            ->buildViews();

        $this->info('Created Successfully.');

        return true;
    }

    /**
     * Build the Controller Class and save in app/Http/Controllers.
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     *
     * @return $this
     */
    protected function buildController()
    {
        $path = $this->_getControllerPath($this->name);

        if ($this->files->exists($path) && $this->ask('Controller exists. Overwrite (y/n)?', 'y') == 'n') {
            return $this;
        }

        $this->info('Creating Controller...');

        $replace = $this->buildReplacements();

        $template = str_replace(
            array_keys($replace),
            array_values($replace),
            $this->getStub('Controller')
        );

        $this->write($path, $template);

        return $this;
    }

    /**
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     *
     * @return $this
     */
    protected function buildModel()
    {
        $path = $this->_getModelPath($this->name);

        if ($this->files->exists($path) && $this->ask('Model exists. Overwrite (y/n)?', 'y') == 'n') {
            return $this;
        }

        $this->info('Creating Model...');

        // Make the models attributes and replacement
        $replace = array_merge($this->buildReplacements(), $this->modelReplacements());

        $template = str_replace(
            array_keys($replace),
            array_values($replace),
            $this->getStub('Model')
        );

        $this->write($path, $template);

        return $this;
    }

    /**
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     * @throws \Exception
     *
     * @return $this
     */
    protected function buildViews()
    {
        $this->info('Creating Views ...');

        $tableHead = "\n";
        $tableBody = "\n";
        $tableColumnFilters = "\n";
        $viewRows = "\n";
        $form = "\n";

        foreach ($this->getFilteredColumns() as $column) {
            $title = Str::title(str_replace('_', ' ', $column));

            $tableHead .= $this->getHead($title, $column);
            $tableBody .= $this->getBody($column);
            $tableColumnFilters .= $this->getTableColumnFilters($column);
            $viewRows .= $this->getField($title, $column, 'view-field');
            $form .= $this->getField($title, $column, 'form-field');
        }

        $replace = array_merge($this->buildReplacements(), [
            '{{tableHeader}}'        => $tableHead,
            '{{tableBody}}'          => $tableBody,
            '{{tableColumnFilters}}' => $tableColumnFilters,
            '{{viewRows}}'           => $viewRows,
            '{{form}}'               => $form,
        ]);

        $this->buildLayout();

        foreach ($this->stubs as $view) {
            $viewTemplate = str_replace(
                array_keys($replace),
                array_values($replace),
                $this->getStub("views/{$view}")
            );

            $this->write($this->_getViewPath($view), $viewTemplate);
        }

        return $this;
    }

    /**
     * Make the class name from table name.
     *
     * @return string
     */
    private function _buildClassName()
    {
        return Str::studly(Str::singular($this->table));
    }
}
