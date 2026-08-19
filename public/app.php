<?php
require __DIR__ . '/../config/bootstrap.php';
Session::start();
Auth::requireLogin();

$route = (string) ($_GET['r'] ?? 'dashboard');

match ($route) {
    'dashboard' => DashboardController::index(),

    'programmes'         => ProgrammeController::index(),
    'programmes.store'   => ProgrammeController::store(),
    'programmes.show'    => ProgrammeController::show(),
    'programmes.status'  => ProgrammeController::status(),

    'centres'          => CentreController::index(),
    'centres.store'    => CentreController::store(),
    'centres.show'     => CentreController::show(),
    'centres.update'   => CentreController::update(),
    'rooms.store'      => CentreController::addRoom(),
    'rooms.status'     => CentreController::roomStatus(),
    'equipment.store'  => CentreController::addEquipment(),

    'staff'          => StaffController::index(),
    'staff.assign'   => StaffController::assign(),
    'users'          => StaffController::users(),
    'users.store'    => StaffController::storeUser(),
    'users.status'   => StaffController::userStatus(),

    'cohorts'           => OperationsController::cohorts(),
    'cohorts.store'     => OperationsController::storeCohort(),
    'cohorts.show'      => OperationsController::showCohort(),
    'cohorts.status'    => OperationsController::cohortStatus(),
    'classgroups.store' => OperationsController::storeClassGroup(),
    'sessions.store'    => OperationsController::storeSession(),

    'timetable' => OperationsController::timetable(),
    'calendar'  => OperationsController::calendar(),

    'packages'          => PackageController::index(),
    'packages.store'    => PackageController::store(),
    'packages.show'     => PackageController::show(),
    'packages.update'   => PackageController::update(),
    'packages.features' => PackageController::features(),

    'subscription'         => SubscriptionController::mine(),
    'subscription.request' => SubscriptionController::request(),
    'subscription.cancel'  => SubscriptionController::cancelMine(),

    'subscriptions'          => SubscriptionController::index(),
    'subscriptions.activate' => SubscriptionController::activate(),
    'subscriptions.void'     => SubscriptionController::voidSub(),

    'apply'                 => ApplicationController::apply(),
    'apply.store'           => ApplicationController::store(),
    'myapplications'        => ApplicationController::mine(),
    'applications'          => ApplicationController::index(),
    'applications.show'     => ApplicationController::show(),
    'applications.review'   => ApplicationController::review(),
    'applications.decide'   => ApplicationController::decide(),
    'applications.enrol'    => ApplicationController::enrol(),
    'applications.withdraw' => ApplicationController::withdraw(),

    'documents.upload'   => ApplicationController::uploadDocument(),
    'documents.download' => ApplicationController::downloadDocument(),
    'documents.status'   => ApplicationController::documentStatus(),

    'courses'             => CourseController::index(),
    'courses.store'       => CourseController::store(),
    'courses.show'        => CourseController::show(),
    'courses.update'      => CourseController::update(),
    'courses.link'        => CourseController::linkProgrammes(),
    'modules.store'       => CourseController::storeModule(),
    'modules.delete'      => CourseController::deleteModule(),
    'lessons.store'       => CourseController::storeLesson(),
    'lessons.edit'        => CourseController::editLesson(),
    'lessons.update'      => CourseController::updateLesson(),
    'lessons.delete'      => CourseController::deleteLesson(),
    'materials.store'     => CourseController::storeMaterial(),
    'materials.delete'    => CourseController::deleteMaterial(),
    'assignments.store'   => CourseController::storeAssignment(),
    'assignments.status'  => CourseController::assignmentStatus(),

    'learn'              => LearnController::index(),
    'learn.course'       => LearnController::course(),
    'learn.lesson'       => LearnController::lesson(),
    'learn.progress'     => LearnController::toggleProgress(),
    'learn.submit'       => LearnController::submitAssignment(),
    'learn.certificates' => LearnController::certificates(),
    'materials.download' => LearnController::downloadMaterial(),
    'submissions.download' => LearnController::downloadSubmission(),

    'invoices'        => FinanceController::invoices(),
    'invoices.store'  => FinanceController::storeInvoice(),
    'invoices.show'   => FinanceController::showInvoice(),
    'invoices.void'   => FinanceController::voidInvoice(),
    'invoices.pay'    => FinanceController::payInvoice(),

    'payments'        => FinanceController::payments(),
    'payments.show'   => FinanceController::showPayment(),
    'payments.cash'   => FinanceController::recordCash(),
    'payments.verify' => FinanceController::verifyPayment(),
    'verify'          => FinanceController::verificationQueue(),
    'proofs.download' => FinanceController::downloadProof(),

    'expenses'        => FinanceController::expenses(),
    'expenses.store'  => FinanceController::storeExpense(),
    'expenses.decide' => FinanceController::decideExpense(),

    'refunds'         => FinanceController::refunds(),
    'refunds.store'   => FinanceController::storeRefund(),
    'refunds.decide'  => FinanceController::decideRefund(),

    'reports'           => FinanceController::reports(),
    'reports.reconcile' => FinanceController::reconcile(),
    'billing'           => FinanceController::billing(),

    'grading'       => GradingController::queue(),
    'grading.show'  => GradingController::show(),
    'grading.grade' => GradingController::grade(),

    'students'          => StudentController::index(),
    'students.show'     => StudentController::show(),
    'students.status'   => StudentController::setStatus(),
    'students.transfer' => StudentController::transfer(),

    'overrides'        => SubscriptionController::overrides(),
    'overrides.store'  => SubscriptionController::overrideStore(),
    'overrides.remove' => SubscriptionController::overrideRemove(),

    'attendance'       => OperationsController::attendanceIndex(),
    'attendance.mark'  => OperationsController::attendanceMark(),
    'attendance.save'  => OperationsController::attendanceSave(),

    'settings'        => PlatformController::settings(),
    'settings.update' => PlatformController::settingsUpdate(),
    'audit'           => PlatformController::audit(),

    default => (function () {
        http_response_code(404);
        echo '<p style="font:14px system-ui;padding:40px">Page not found. <a href="app.php">Back to dashboard</a></p>';
    })(),
};
