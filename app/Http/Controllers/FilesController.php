<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Services\Filesystem\Disks\NotesDisk;

class FilesController extends Controller {
    public function __construct(protected NotesDisk $disk) {
        // empty
    }
    public function __invoke(File $file) {
    }
}
