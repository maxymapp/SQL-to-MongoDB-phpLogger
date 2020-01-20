<?php

namespace LogBundle\Controller;

use LogBundle\Form\Type\LogFormReportType;
use UserBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Finder\Finder;
use LogBundle\Form\Type\LogSearchFormType;
use Symfony\Component\HttpFoundation\Request;

class displayLogsController extends Controller
{

    public function selectYearAction()
    {
        if (false === $this->get('security.authorization_checker')->isGranted('ROLE_ADMIN'))
        {
            throw $this->createNotFoundException();
        }

        $years = array();

        $finder = new Finder();
        $finder->directories()->in($this->container->getParameter('app_paths.logs'));
        $finder->depth('== 0');
        $finder->sortByName();

        foreach ($finder as $directory)
        {
            $years[] = $directory->getFilename();

        }

        $years = array_reverse($years);

        return $this->render('LogBundle::selectYear.html.twig', array('years' => $years));
    }

    public function selectWeekAction($year)
    {
        if (false === $this->get('security.authorization_checker')->isGranted('ROLE_ADMIN'))
        {
            throw $this->createNotFoundException();
        }

        $logFiles = array();

        $finder = new Finder();
        $finder->files()->in($this->container->getParameter('app_paths.logs').'/'.$year);
        $finder->depth('== 0');
        $finder->sortByName();

        foreach ($finder as $logFile)
        {
            $logFiles[] = $logFile->getFilename();

        }

        $logFiles = array_reverse($logFiles);

        return $this->render('LogBundle::selectWeek.html.twig', array('year' => $year, 'logFiles' => $logFiles));
    }

    public function displayLogAction($year, $logFile)
    {
        if (false === $this->get('security.authorization_checker')->isGranted('ROLE_ADMIN'))
        {
            throw $this->createNotFoundException();
        }

        $logContent = array();
        $userIdList = [];

        $file = $this->container->getParameter('app_paths.logs').'/'.$year.'/'.$logFile;
        $handle = fopen($file, 'r');

        if ($handle) {
            while (($buffer = fgets($handle)) !== false)
            {
                $pos = strpos($buffer, ']');
                $dateStamp = substr($buffer, 1, $pos-1);
                $buffer = str_replace('['.$dateStamp.']:', '', $buffer);

                $pos = strpos($buffer, ']');
                $timeZone = substr($buffer, 1, $pos-1);
                $buffer = str_replace('['.$timeZone.']:', '', $buffer);

                $pos = strpos($buffer, ']');
                $ipAddress = substr($buffer, 1, $pos-1);
                $buffer = str_replace('['.$ipAddress.']:', '', $buffer);

                $pos = strpos($buffer, ']');
                $userId = substr($buffer, 1, $pos-1);
                $buffer = str_replace('['.$userId.']:', '', $buffer);

                $pos = strpos($buffer, ']');
                $pageAddress = substr($buffer, 1, $pos-1);
                $buffer = str_replace('['.$pageAddress.']:', '', $buffer);

                $pos = strpos($buffer, ']');
                $transactionDescription = substr($buffer, 1, $pos-1);

                if (!in_array($userId, $userIdList)) {
                    $userIdList[] = $userId;
                }

                $logContent[] = array(
                    'dateStamp'   => \DateTime::createFromFormat('Y-d-m H:i:s', $dateStamp),
                    'timeZone'    => $timeZone,
                    'ipAddress'   => $ipAddress,
                    'userId'      => $userId,
                    'pageAddress' => $pageAddress,
                    'description' => $transactionDescription
                );
            }
            if (!feof($handle))
            {
                throw new \RuntimeException('Error: unexpected fgets() fail when reading log file.');
            }
            fclose($handle);
        }

        /** @var User[] $users */
        $users = $this->getDoctrine()->getRepository('UserBundle:User')->findAllInList($userIdList);
        if (null === $users) {
            throw new \RuntimeException('No users were found.');
        }

        foreach ($users as $user)
        {
            foreach ($logContent as &$line)
            {
                if ($line['userId'] == $user->getId())
                {
                    if ($user->getCurrentRole() == 'ROLE_CLIENT')
                    {
                        $line['userName'] = $user->getCompany();
                    }
                    else
                    {
                        $line['userName'] = $user->getFirstName().' '.$user->getLastName();
                    }

                    switch ($user->getCurrentRole())
                    {
                        case 'ROLE_DATA_PROCESSOR' : { $currentUserRole = 'Data Processor'; break; }
                        case 'ROLE_PRODUCTION_MGR' : { $currentUserRole = 'Production Mgr'; break; }
                        case 'ROLE_ENVELOPE_MGR'   : { $currentUserRole = 'Envelope Mgr'; break; }
                        case 'ROLE_OFFSET_MGR'     : { $currentUserRole = 'Offset Mgr'; break; }
                        case 'ROLE_CLIENT'         : { $currentUserRole = 'Client';         break; }
                        case 'ROLE_SALES_REP'      : { $currentUserRole = 'Sales Rep';      break; }
                        case 'ROLE_SALES_REP_MGR'  : { $currentUserRole = 'Sales Rep Mgr';  break; }
                        case 'ROLE_COORDINATOR'    : { $currentUserRole = 'Coordinator';    break; }
                        case 'ROLE_ADMIN'          : { $currentUserRole = 'Admin';          break; }
                        case 'ROLE_SUPER_ADMIN'    : { $currentUserRole = 'Super Admin';    break; }
                        case 'ROLE_TECH_SUPPORT'   : { $currentUserRole = 'Tech Support';   break; }
                        default                    : { $currentUserRole = 'unknown';        break; }
                    }

                    $line['userRole'] = $currentUserRole;
                }
            }
        }

        $logContent = array_reverse($logContent);

        return $this->render('LogBundle::displayLog.html.twig', array('logContent' => $logContent));
    }

    public function searchLogsAction(Request $request)
    {
        if (false === $this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
            throw $this->createNotFoundException();
        }

        $form  = $this->createForm(new LogSearchFormType());
        $error = 'none';

        if ($request->isMethod('POST')) {
            $form->bind($request);

            $searchFor = $request->get('ms_logger_search');

            if (strlen($searchFor['keyword']) >= 3) {
                $pattern = preg_quote($searchFor['keyword'], '/');
                $pattern = "/^.*$pattern.*\$/mi";

                $found = [];

                $finder = new Finder();
                $finder->files()->in($this->container->getParameter('app_paths.logs'));
                $finder->depth('== 1');
                $finder->sortByName();

                foreach ($finder as $logFile) {
                    $contents = file_get_contents($logFile);

                    if (preg_match_all($pattern, $contents, $matches)) {
                        implode($matches[0]);
                    }
                    if (!empty($matches[0])) {
                        $found[] = [
                            'fileName' => $logFile->getFilename(),
                            'year'     => substr($logFile->getPath(), -4),
                            'lines'    => $matches,
                        ];
                    }
                }

                return $this->render('LogBundle::displaySearchResults.html.twig', ['searchResults' => $found]);
            } else {
                $error = 'Please type at least three characters';
            }

        }

        return $this->render('LogBundle::searchLog.html.twig', ['form' => $form->createView(), 'error' => $error]);
    }

    public function displayReportAction(Request $request)
    {
        if (false === $this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
            throw $this->createNotFoundException();
        }

        $form = $this->createForm(LogFormReportType::class);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $dateStart = new \DateTimeImmutable($form->get("start_date")->getData());
            $dateEnd   = new \DateTimeImmutable($form->get("end_date")->getData());
            $newArray  = $this->get('maksym_activity_logger')
                              ->getCoordinatingLogsByPeriod($dateStart, $dateEnd);
        }

        return $this->render('LogBundle::reportList.html.twig', [
            "logContent" => !empty($newArray) ? $newArray : [],
            "form"       => $form->createView(),
        ]);
    }

}