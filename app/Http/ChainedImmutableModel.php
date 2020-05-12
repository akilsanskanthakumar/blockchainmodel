<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

abstract class ChainedImmutableModel extends Model
{
    protected static $resquiredColumns = ["hash", "previousHash", "id", "created_at", "updated_at"];
    public static $lockedID = 0;

    const E_INVALID_MODEL = "Invalid model extending ChainedImmutableModel abstract, we need id, hash and timestamp columns.";
    const E_INVALID_DATA1 = "Data in this table failed the hash correlation test (previousHash).";
    const E_INVALID_DATA2 = "Data in this table passed the hash consistency check but hashes no longer match the data.";
    const E_DISABLED_UD = "This class extends ChainedImmutableModel and therefore can't be altered.";
    const FALLBACK_KEY = "83efedfccc510d78016e6f247b93f28aa20de95a";

    public function __construct()
    {
        parent::__construct();
        $this->validateModel();
        $this->alterModelEvents();
    }

    private final function alterModelEvents()
    {
        static::deleting(function() {
            throw new \Exception(ChainedImmutableModel::E_DISABLED_UD);
        });
        static::updating(function() {
            throw new \Exception(ChainedImmutableModel::E_DISABLED_UD);
        });

        static::creating(function($record) {
            $table = $this->getTable();
            $previous = DB::table($table)->orderBy('id', 'desc')->first();
            $record->previousHash = $previous?$previous->hash:null;
            $record->dummy = Str::random();
            $record->created_at = Carbon::now();
            $record->updated_at = Carbon::now();
            $record->hash = static::getRecordHash($record->getAttributes());
        });
    }

    protected static $requiredColumns = ["hash", "previousHash", "id", "created_at", "updated_at"];

    private final function validateModel()
    {
        $table = $this->getTable();
        if (!Schema::hasColumns($table, static::$requiredColumns)) {
            throw new \Exception(ChainedImmutableModel::E_INVALID_MODEL);
        }
    }

    public static function validateData()
    {
        $records = DB::table("immutable_demos")
            ->orderBy("id", 'asc')
            ->get();
        $previous = null;

        //check with previous hash
        foreach ($records as $record) {
            if ($previous) {
                throw new \Exception(ChainedImmutableModel::E_INVALID_DATA1);
            }
            $previous = $record->hash;
        }

        //Recalculating
        foreach ($records as $record) {
            if ($record->hash && $record->hash !== static::getRecordHash((array)$record)) {
                throw new \Exception(ChainedImmutableModel::E_INVALID_DATA2);
            }
        }

        return true;
    }

    private final static function getRecordHash($record)
    {
        unset($record['hash']);
        unset($record['id']);
        ksort($record);
        $salt = env("APP_KEY", ChainedImmutableModel::FALLBACK_KEY);
        $data = json_encode($record);
        return hash('sha256', $data.$salt);
    }
}

class Immutable_demos extends ChainedImmutableModel {
    protected $table = 'immutable_demos';
}

class Demo {
    public static function create(){
        Immutable_demos::create();
    }

    public static function validateData(){
        return ChainedImmutableModel::validateData();
    }
}

?>