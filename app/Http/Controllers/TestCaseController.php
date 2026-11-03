<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\TestCases\CreateTestCase;
use App\Actions\TestCases\RecordTestCaseResult;
use App\Actions\TestCases\UpdateTestCase;
use App\Http\Requests\TestCases\RecordTestCaseResultRequest;
use App\Http\Requests\TestCases\SaveTestCaseRequest;
use App\Http\Requests\TestCases\TestCaseIndexRequest;
use App\Http\Resources\FormOptions;
use App\Http\Resources\TestCaseRowResource;
use App\Models\Item;
use App\Models\TestCase;
use App\Queries\TestCaseListQuery;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class TestCaseController extends Controller
{
    public function index(TestCaseIndexRequest $request): Response
    {
        $this->authorize('viewAny', TestCase::class);

        $filters = $request->filters();
        $query = new TestCaseListQuery($request->user());

        return Inertia::render('test-cases/index', [
            'testCases' => TestCaseRowResource::collection($query->paginate($filters)),
            'counts' => $query->counts($filters),
            'filters' => $filters,
            'options' => ['projects' => FormOptions::projects($request->user())],
        ]);
    }

    public function store(SaveTestCaseRequest $request, Item $item, CreateTestCase $create): RedirectResponse
    {
        $create->handle($item, $request->user(), $request->testCaseData());

        return back();
    }

    public function update(SaveTestCaseRequest $request, TestCase $testCase, UpdateTestCase $update): RedirectResponse
    {
        $update->handle($testCase, $request->user(), $request->testCaseData());

        return back();
    }

    public function result(RecordTestCaseResultRequest $request, TestCase $testCase, RecordTestCaseResult $record): RedirectResponse
    {
        $record->handle($testCase, $request->user(), $request->result(), $request->input('actual_result'));

        return back();
    }

    public function destroy(TestCase $testCase): RedirectResponse
    {
        $this->authorize('delete', $testCase);

        $testCase->delete();

        return back();
    }
}
