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

    // Every signed-in user's own account settings — name, phone, photo. No permission
    // check beyond being logged in; identity.profile.update is ○ (own records only)
    // for every role (03-rbac.md §5).
    'profile'        => ProfileController::edit(),
    'profile.update' => ProfileController::update(),
    'profile.photo'  => ProfileController::photo(),

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

    // Assessments — quizzes and examinations (README §18-§20).
    'assessments.manage'         => AssessmentController::manage(),
    'assessments.store'          => AssessmentController::store(),
    'assessments.edit'           => AssessmentController::edit(),
    'assessments.update'         => AssessmentController::update(),
    'assessments.status'         => AssessmentController::status(),
    'assessments.question.store'  => AssessmentController::storeQuestion(),
    'assessments.question.delete' => AssessmentController::deleteQuestion(),
    'assessments.attempts'       => AssessmentController::attempts(),
    'assessments.start'          => AssessmentController::start(),
    'assessments.take'           => AssessmentController::take(),
    'assessments.submit'         => AssessmentController::submit(),
    'assessments.result'         => AssessmentController::result(),
    'assessments.marking'        => AssessmentController::markingQueue(),
    'assessments.mark'           => AssessmentController::mark(),
    'assessments.marks.save'     => AssessmentController::saveMarks(),

    // Donations — staff side. The public giving flow is public/donate.php, which has
    // no session by design (README §9b).
    'donations'                   => DonationController::index(),
    'donations.export'            => DonationController::export(),
    'donations.campaigns'         => DonationController::campaigns(),
    'donations.campaigns.store'   => DonationController::storeCampaign(),
    'donations.campaign'          => DonationController::campaign(),
    'donations.campaigns.update'  => DonationController::updateCampaign(),
    'donations.campaigns.status'  => DonationController::campaignStatus(),

    // Affiliate programme (README §25). The affiliate's own pages — dashboard and
    // application form — moved to their own portal (public/affiliate/), the same
    // separation careers has from this app. Only the staff side stays here.
    'affiliate.admin'              => AffiliateController::index(),
    'affiliate.show'               => AffiliateController::show(),
    'affiliate.decide'             => AffiliateController::decide(),
    'affiliate.commissions'        => AffiliateController::commissions(),
    'affiliate.commissions.decide' => AffiliateController::decideCommission(),
    'affiliate.payout'             => AffiliateController::createPayout(),
    'affiliate.payout.paid'        => AffiliateController::markPaid(),

    // Management dashboards and reporting (README §32, §38, §16).
    'management'          => ManagementController::dashboard(),
    'management.centres'  => ManagementController::centres(),
    'management.academic' => ManagementController::academic(),
    'management.export'   => ManagementController::export(),

    // Corporate training (README §46). The public enquiry form is public/corporate.php
    // and the invitation flow is public/corporate-invite.php — both sessionless.
    'corporate'                        => CorporateController::index(),
    'corporate.organisations'          => CorporateController::organisations(),
    'corporate.organisations.store'    => CorporateController::organisationStore(),
    'corporate.organisation'           => CorporateController::organisation(),
    'corporate.organisations.update'   => CorporateController::organisationUpdate(),
    'corporate.contacts.store'         => CorporateController::contactStore(),
    'corporate.requests'               => CorporateController::requests(),
    'corporate.request'                => CorporateController::request(),
    'corporate.requests.store'         => CorporateController::requestStore(),
    'corporate.requests.update'        => CorporateController::requestUpdate(),
    'corporate.proposals.store'        => CorporateController::proposalStore(),
    'corporate.proposal'               => CorporateController::proposal(),
    'corporate.proposals.status'       => CorporateController::proposalStatus(),
    'corporate.contracts'              => CorporateController::contracts(),
    'corporate.contracts.create'       => CorporateController::contractCreate(),
    'corporate.contract'               => CorporateController::contract(),
    'corporate.contracts.status'       => CorporateController::contractStatus(),
    'corporate.participants.store'     => CorporateController::participantStore(),
    'corporate.participants.invite'    => CorporateController::participantInvite(),
    'corporate.participants.withdraw'  => CorporateController::participantWithdraw(),
    'corporate.report'                 => CorporateController::report(),

    'chat'            => ChatController::index(),
    'chat.show'       => ChatController::show(),
    'chat.direct'     => ChatController::startDirect(),
    'chat.group'      => ChatController::createGroup(),
    'chat.cohort'     => ChatController::cohortGroup(),
    'chat.post'       => ChatController::post(),
    'chat.attachment' => ChatController::downloadAttachment(),
    'chat.moderate'   => ChatController::moderate(),
    'chat.mute'       => ChatController::mute(),
    'chat.leave'      => ChatController::leave(),

    'notifications'                  => NotificationController::index(),
    'notifications.open'             => NotificationController::open(),
    'notifications.readall'          => NotificationController::markAllRead(),
    'notifications.preferences'      => NotificationController::preferences(),
    'notifications.preferences.save' => NotificationController::savePreferences(),

    'announcements'         => NotificationController::announcements(),
    'announcements.publish' => NotificationController::publishAnnouncement(),

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

    'utilities'                 => UtilitiesController::index(),
    'utilities.clearcache'      => UtilitiesController::clearCache(),
    'utilities.clearlog'        => UtilitiesController::clearLog(),
    'utilities.toggledebug'     => UtilitiesController::toggleDebug(),
    'utilities.togglehttps'     => UtilitiesController::toggleHttps(),
    'utilities.migrate'         => UtilitiesController::migrate(),
    'utilities.importdemo'      => UtilitiesController::importDemo(),
    'utilities.resetdb'         => UtilitiesController::resetDb(),

    'recruitment'                    => RecruitmentAdminController::index(),
    'recruitment.jobs'               => RecruitmentAdminController::jobs(),
    'recruitment.jobs.create'        => RecruitmentAdminController::jobCreate(),
    'recruitment.jobs.store'         => RecruitmentAdminController::jobStore(),
    'recruitment.jobs.edit'          => RecruitmentAdminController::jobEdit(),
    'recruitment.jobs.update'        => RecruitmentAdminController::jobUpdate(),
    'recruitment.jobs.publish'       => RecruitmentAdminController::jobPublish(),
    'recruitment.jobs.unpublish'     => RecruitmentAdminController::jobUnpublish(),
    'recruitment.jobs.close'         => RecruitmentAdminController::jobClose(),
    'recruitment.jobs.duplicate'     => RecruitmentAdminController::jobDuplicate(),
    'recruitment.jobs.questions'         => RecruitmentAdminController::jobQuestions(),
    'recruitment.jobs.questions.store'   => RecruitmentAdminController::jobQuestionStore(),
    'recruitment.jobs.questions.delete'  => RecruitmentAdminController::jobQuestionDelete(),

    'recruitment.departments'       => RecruitmentAdminController::departments(),
    'recruitment.departments.store' => RecruitmentAdminController::departmentStore(),
    'recruitment.categories'        => RecruitmentAdminController::categories(),
    'recruitment.categories.store'  => RecruitmentAdminController::categoryStore(),

    'recruitment.applications'          => RecruitmentAdminController::applications(),
    'recruitment.applications.show'     => RecruitmentAdminController::applicationShow(),
    'recruitment.applications.review'   => RecruitmentAdminController::applicationReview(),
    'recruitment.applications.decide'   => RecruitmentAdminController::applicationDecide(),
    'recruitment.notes.store'           => RecruitmentAdminController::noteStore(),
    'recruitment.documents.download'    => RecruitmentAdminController::documentDownload(),
    'recruitment.reports'               => RecruitmentAdminController::reports(),

    'recruitment.emailtemplates'        => RecruitmentAdminController::emailTemplates(),
    'recruitment.emailtemplates.edit'   => RecruitmentAdminController::emailTemplateEdit(),
    'recruitment.emailtemplates.save'   => RecruitmentAdminController::emailTemplateSave(),
    'recruitment.emaillogs'             => RecruitmentAdminController::emailLogs(),

    'recruitment.interviews.store'          => InterviewController::store(),
    'recruitment.interviews.reschedule'     => InterviewController::reschedule(),
    'recruitment.interviews.status'         => InterviewController::updateStatus(),
    'recruitment.interviews.mine'           => InterviewController::myInterviews(),
    'recruitment.interviews.feedback'       => InterviewController::feedback(),
    'recruitment.interviews.feedback.store' => InterviewController::feedbackStore(),

    default => (function () {
        http_response_code(404);
        echo '<p style="font:14px system-ui;padding:40px">Page not found. <a href="app.php">Back to dashboard</a></p>';
    })(),
};
