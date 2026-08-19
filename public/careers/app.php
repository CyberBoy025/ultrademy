<?php
require __DIR__ . '/../../config/bootstrap.php';
Session::start('ultrademy_careers_session');

// Deliberately no blanket Auth::requireLogin() here — most careers routes are public
// (docs/architecture/16-careers-portal.md §3). Routes that need an account check
// Auth::check() themselves and redirect to careers' own login.php, never the LMS one.
$route = (string) ($_GET['r'] ?? 'home');

match ($route) {
    'home' => JobController::home(),

    'jobs'         => JobController::index(),
    'jobs.show'    => JobController::show(),
    'jobs.save'    => JobController::save(),
    'jobs.unsave'  => JobController::unsave(),

    'dashboard' => CareersDashboardController::index(),

    'profile.personal'          => ApplicantProfileController::personal(),
    'profile.personal.save'     => ApplicantProfileController::personalSave(),
    'profile.education'         => ApplicantProfileController::education(),
    'profile.education.store'   => ApplicantProfileController::educationStore(),
    'profile.education.delete'  => ApplicantProfileController::educationDelete(),
    'profile.experience'        => ApplicantProfileController::experience(),
    'profile.experience.store'  => ApplicantProfileController::experienceStore(),
    'profile.experience.delete' => ApplicantProfileController::experienceDelete(),
    'profile.skills'            => ApplicantProfileController::skills(),
    'profile.skills.store'      => ApplicantProfileController::skillsStore(),
    'profile.skills.delete'     => ApplicantProfileController::skillsDelete(),
    'profile.certifications'         => ApplicantProfileController::certifications(),
    'profile.certifications.store'   => ApplicantProfileController::certificationsStore(),
    'profile.certifications.delete'  => ApplicantProfileController::certificationsDelete(),
    'profile.resume'            => ApplicantProfileController::resume(),
    'profile.resume.upload'     => ApplicantProfileController::resumeUpload(),
    'profile.references'        => ApplicantProfileController::references(),
    'profile.references.store'  => ApplicantProfileController::referencesStore(),
    'profile.references.delete' => ApplicantProfileController::referencesDelete(),
    'profile.review'            => ApplicantProfileController::review(),

    'apply'                    => JobApplicationController::start(),
    'apply.documents'          => JobApplicationController::documents(),
    'apply.documents.store'    => JobApplicationController::documentsStore(),
    'apply.documents.delete'   => JobApplicationController::documentsDelete(),
    'apply.documents.download' => JobApplicationController::documentsDownload(),
    'apply.questions'          => JobApplicationController::questions(),
    'apply.questions.save'     => JobApplicationController::questionsSave(),
    'apply.review'             => JobApplicationController::review(),
    'apply.submit'             => JobApplicationController::submit(),

    'applications'          => JobApplicationController::mine(),
    'applications.show'     => JobApplicationController::show(),
    'applications.withdraw' => JobApplicationController::withdraw(),

    'notifications'                  => CareersNotificationController::index(),
    'notifications.open'             => CareersNotificationController::open(),
    'notifications.readall'          => CareersNotificationController::markAllRead(),
    'notifications.preferences'      => CareersNotificationController::preferences(),
    'notifications.preferences.save' => CareersNotificationController::savePreferences(),

    default => (function () {
        http_response_code(404);
        echo '<p style="font:14px system-ui;padding:40px">Page not found. <a href="app.php">Back to careers home</a></p>';
    })(),
};
