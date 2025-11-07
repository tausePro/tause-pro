@push('script')
    <script>
        document.addEventListener('alpine:init', () => {
            // create video data
            Alpine.data('createVideoData', () => ({
                // steps to create video using creatify
                steps: ['{{ __('Product') }}', '{{ __('Detail') }}', '{{ __('Composition') }}',
                    '{{ __('Render') }}'
                ],
                // currentStep
                currentStep: 1,
                // step percent
                stepPercent: 25,
                // video config data
                videoConfigData: {},
                init() {
                    Alpine.store('createVideoData', this);
                },
                // initialize the variables
                initialize() {
                    this.currentStep = 1;
                    this.videoConfigData = {
                        language: "en",
                    };
                },
                changeStep(step) {
                    const previousStep = this.currentStep;

                    if (typeof step === 'string') {
                        if (step === 'next') {
                            this.currentStep = Math.min(4, this.currentStep + 1);
                        } else if (step === 'prev') {
                            this.currentStep = Math.max(1, this.currentStep - 1);
                        }
                    } else if (typeof step == 'number') {
                        if (this.currentStep > step) {
                            this.currentStep = Math.max(1, step);
                        } else {
                            return;
                        }
                    }

                    if (this.currentStep == 4) {
                        Alpine.store('previewVideoData').generatePreviewVideos();
                    }
                }
            }));

            // step 1: productInformation data
            Alpine.data('productBasicInfo', () => ({
                // when submit the data to server
                submitting: false,
                // form data
                formData: {
                    url: '',
                    title: '',
                    description: '',
                },
                // when upload the files to server
                uploading: false,
                // active tab
                activeTab: "{{ \App\Enums\AiInfluencer\ProductTabEnum::URL->value }}",
                init() {
                    Alpine.store('productBasicInfo', this);
                },
                // initialze the window
                initialize() {
                    this.submitting = false;
                    this.uploading = false;
                    this.activeTab =
                        "{{ \App\Enums\AiInfluencer\ProductTabEnum::URL->value }}";

                    this.formData = {
                        url: '',
                        title: '',
                        description: '',
                    };
                },
                // set active tab
                setActiveTab(tab) {
                    if (this.activeTab === tab) return;
                    this.activeTab = tab;
                },
                // next step
                async nextStep() {
                    if (this.activeTab ==
                        "{{ \App\Enums\AiInfluencer\ProductTabEnum::URL->value }}"
                    ) {
                        if (this.validateWithMsg(this.formData.url, 'Please input url')) {
                            if (/^(https?:\/\/)?(www\.)?([a-zA-Z0-9\-]+\.)+[a-zA-Z]{2,}(:\d+)?(\/\S*)?$/
                                .test(this.formData.url)) {
                                Alpine.store('createVideoData').videoConfigData.productLink = this
                                    .formData.url;
                                Alpine.store('createVideoData').changeStep('next');
                            } else {
                                toastr.warning('{{ __('Please input valid url') }}');
                                return;
                            }
                        }
                    } else {
                        const assets = Alpine.store('assetsDetail').assets.filter(asset => asset
                            .checked);
                        if (assets.length < 1) {
                            toastr.error(
                                '{{ __('Please upload assets and select assets to use it') }}');
                            return;
                        }

                        if (!this.validateWithMsg(this.formData.title, 'Please input title') || !
                            this
                            .validateWithMsg(this.formData.description, 'Please input description')
                        ) {
                            return;
                        }

                        const fileIds = assets.map(asset => asset.fileId);

                        Alpine.store('createVideoData').videoConfigData.productLink = '';
                        Alpine.store('createVideoData').videoConfigData.fileIds = fileIds;
                        Alpine.store('createVideoData').videoConfigData.productName = this.formData
                            .title;
                        Alpine.store('createVideoData').videoConfigData.productDescription = this
                            .formData.description;

                        Alpine.store('createVideoData').changeStep('next');
                    }
                },
                // validate if value is empty or not
                validateWithMsg(value, errorMsg) {
                    if (!value || value.trim() == '') {
                        toastr.error(errorMsg);
                        return false;
                    }
                    return true;
                },
            }));

            Alpine.data('fileUploadData', () => ({
                // accept file types
                accept: "{{ \App\Packages\Topview\Enums\UploadFileFormat::accepts() }}",
                // if any files selected or not
                fileSelected: false,
                initialize() {
                    this.fileSelected = false;
                    this.$el.querySelector('input[type="file"]').files = null;
                },
                // set file selected status
                setFileSelected(status = true) {
                    this.fileSelected = status;
                },
                // upload the file
                async uploadFiles() {
                    if (!this.fileSelected) {
                        toastr.error('{{ __('Please select the files') }}')
                        return;
                    }

                    Alpine.store('productBasicInfo').uploading = true;

                    try {
                        const formData = new FormData(this.$el);

                        const res = await fetch(
                            "{{ route('topview.upload.upload-files') }}", {
                                method: 'post',
                                headers: {
                                    'Accept': 'application/json'
                                },
                                body: formData
                            }
                        );

                        if (!res.ok) {
                            toastr.error(res.message ||
                                "{{ __('Unexpected issue happen while file upload') }}");
                            throw new Error(res.message ||
                                'Unexpected issue happen while file upload');
                        }

                        const resData = await res.json();

                        if (resData.status == 'error') {
                            toastr.error(resData.message ||
                                "{{ __('Unexpected issue happen while file upload') }}");
                            throw new Error(resData.message ||
                                'Unexpected issue happen while file upload');
                        }

                        Alpine.store('assetsDetail').addAssets(resData.uploadedFiles);
                        Alpine.store('productBasicInfo').uploading = false;
                        this.$el.querySelector('input[type="file"]').files = null;
                        this.fileSelected = false;

                    } catch (error) {
                        console.error(error);
                        Alpine.store('productBasicInfo').uploading = false;
                    }
                }
            }))

            Alpine.data('assetsDetail', () => ({
                // if all assets are selcted or not
                isSelectedAll: false,
                // video and image assets
                assets: [],
                init() {
                    Alpine.store('assetsDetail', this);
                },
                initialize() {
                    this.assets = [];
                    this.isSelectedAll = false;
                },
                // add assets
                addAssets(files) {
                    let id = this.assets.length;

                    files.forEach(file => {
                        file.id = id;
                        file.checked = true;
                        id++;

                        this.assets.push(file);
                    })

                    this.isSelectedAll = this.assets.every(asset => asset.checked);
                },
                // toggle all select
                toggleSelect() {
                    this.isSelectedAll = !this.isSelectedAll;
                    this.assets.forEach(asset => asset.checked = this.isSelectedAll);
                },
                /**select the asset */
                selectAsset(id) {
                    const asset = this.assets.find(asset => asset.id == id);
                    asset.checked = !asset.checked;
                    this.isSelectedAll = this.assets.every(asset => asset.checked);
                },
                /**Display the video duration to human like*/
                timeConvert(time) {
                    time = time ?? 0;

                    const minutes = Math.floor(time / 60);
                    const seconds = Math.floor(time % 60);
                    return (minutes > 10 ? minutes : '0' + minutes) + ':' + (seconds > 10 ? seconds :
                        '0' + seconds);
                },
            }))

            // setp 3: video composition data
            Alpine.data('videoCompositionData', () => ({
                // fetching status
                fetching: false,
                // active tabl
                activeTab: '',
                // resources like avatars, musics, voices
                resources: {
                    avatars: [],
                    voices: [],
                    captions: [],
                },
                // selected resources
                selectedResources: {
                    avatarId: '',
                    voiceId: '',
                    captionId: '',
                },
                // search key for resources
                searchKey: '',
                initialize() {
                    this.fetching = false;
                    this.activeTab =
                        "{{ \App\Enums\AiInfluencer\CompositionEditTabEnum::AVATAR->value }}";

                    if (this.resources.avatars.length == 0) {
                        this.fetchAvatars();
                    }
                    if (this.resources.voices.length == 0) {
                        this.fetchVoices();
                    }
                    if (this.resources.captions.length == 0) {
                        this.fetchCaptions();
                    }

                    this.selectedResources = {
                        avatarId: '',
                        voiceId: '',
                        captionId: '',
                    };
                },
                // fetch avatars
                async fetchAvatars(pageNo = 1) {
                    try {
                        const res = await fetch(
                            '{{ route('topview.general.ai-avatar-query') }}' +
                            `?pageNo=${pageNo}&pageSize=100`, {
                                headers: {
                                    'Accept': 'application/json',
                                }
                            }
                        )

                        if (!res.ok) {
                            throw new Error("Error happen while fetching avatars");
                        }

                        const resData = await res.json();

                        if (resData.status == 'error') {
                            throw new Error(resData.message ||
                                'Something went wrong. Please contact support for assistance');
                        }

                        this.resources.avatars.push(...resData.resData.data);

                        if (this.resources.avatars.length < resData
                            .resData.total) {
                            this.fetchAvatars(pageNo + 1);
                        }
                    } catch (error) {
                        console.error(error);
                    }
                },
                // select the avatar
                selectAvatar(id) {
                    if (this.selectedResources.avatarId == id) return;
                    this.selectedResources.avatarId = id;
                },
                // fetch voices
                async fetchVoices(pageNo = 1) {
                    try {
                        const res = await fetch(
                            '{{ route('topview.general.voice-query') }}' +
                            `?pageNo=${pageNo}&pageSize=100`, {
                                headers: {
                                    'Accept': 'application/json',
                                }
                            }
                        )

                        if (!res.ok) {
                            throw new Error("Error happen while fetching voices");
                        }

                        const resData = await res.json();

                        if (resData.status == 'error') {
                            throw new Error(resData.message ||
                                'Something went wrong. Please contact support for assistance');
                        }

                        this.resources.voices.push(...resData.resData.data);

                        if (this.resources.voices.length < resData.resData.total) {
                            this.fetchVoices(pageNo + 1);
                        }
                    } catch (error) {
                        console.error(error);
                    }
                },
                // select the voice
                selectVoice(id) {
                    if (this.selectedResources.voiceId == id) return;
                    this.selectedResources.voiceId = id;
                },
                // fetch captions
                async fetchCaptions() {
                    try {
                        const res = await fetch(
                            '{{ route('topview.general.caption-list') }}', {
                                headers: {
                                    'Accept': 'application/json',
                                }
                            }
                        )

                        if (!res.ok) {
                            throw new Error("Error happen while fetching captions");
                        }

                        const resData = await res.json();

                        if (resData.status == 'error') {
                            throw new Error(resData.message ||
                                'Something went wrong. Please contact support for assistance');
                        }

                        this.resources.captions = resData.resData;
                    } catch (error) {
                        console.error(error);
                    }
                },
                // select the caption
                selectCaption(id) {
                    if (this.selectedResources.captionId == id) return;
                    this.selectedResources.captionId = id;
                },
                // set active tab
                setActiveTab(tab) {
                    if (this.activeTab == 'tab') return;
                    this.activeTab = tab;
                },
                // next step => preview videos
                nextStep() {
                    if (this.selectedResources.avatarId.trim() != '') {
                        Alpine.store('createVideoData').videoConfigData.aiavatarId = this
                            .selectedResources.avatarId;
                    }
                    if (this.selectedResources.voiceId.trim() != '') {
                        Alpine.store('createVideoData').videoConfigData.voiceId = this
                            .selectedResources.voiceId;
                    }
                    if (this.selectedResources.captionId.trim() != '') {
                        Alpine.store('createVideoData').videoConfigData.captionId = this
                            .selectedResources.captionId;
                    }

                    Alpine.store('createVideoData').changeStep('next');
                },
            }));

            // composition voice tab
            Alpine.data('compositionVoiceData', () => ({
                // playing voice
                playingVoice: null,
                initialize() {
                    this.playingVoice = null;
                },
                // play or pause the voice
                playVoice(voice) {
                    if (!this.playingVoice) {
                        this.playingVoice = voice;
                    } else if (this.playingVoice.voiceId != voice.voiceId) {
                        if (this.playingVoice.playing) {
                            this._changeVoicePlayStatus(this.playingVoice);
                        }
                        this.playingVoice = voice;
                    }

                    this._changeVoicePlayStatus(voice);
                },
                // when click outside of area, it will pause the playing voice
                pausePlayingVoice() {
                    if (this.playingVoice) {
                        if (this.playingVoice && this.playingVoice.playing) {
                            this._changeVoicePlayStatus(this.playingVoice);
                        }
                        this.playingVoice = null;
                    }
                },
                // change voice play status
                _changeVoicePlayStatus(voice) {
                    const audioElement = document.getElementById('composition-voice-' + voice
                        .voiceId);
                    voice.playing = !voice.playing;

                    if (voice.playing) {
                        audioElement.play();
                    } else {
                        audioElement.pause();
                        audioElement.currentTime = 0;
                    }
                }
            }));

            // setp 4: preview video
            Alpine.data('previewVideoData', () => ({
                // fetching status
                fetching: false,
                // selected preview media job
                scriptId: '',
                // generate preview video task id
                taskId: '',
                // playing voice
                playingVideo: null,
                // preview videos
                previews: [],
                init() {
                    Alpine.store('previewVideoData', this);
                },
                // initialize
                initialize() {
                    this.fetching = false;
                    this.previews = [];
                    this.scriptId = '';
                    this.taskId = '';
                    this.playingVideo = null;
                },
                // play or pause the voice
                playVideo(video) {
                    if (!this.playingVideo) {
                        this.playingVideo = video;
                    } else if (this.playingVideo.scriptId != video.scriptId) {
                        if (this.playingVideo.playing) {
                            this._changeVideoPlayStatus(this.playingVideo);
                        }
                        this.playingVideo = video;
                    }

                    this._changeVideoPlayStatus(video);
                },
                // when click outside of area, it will pause the playing video
                pausePlayingVideo() {
                    if (this.playingVideo) {
                        if (this.playingVideo && this.playingVideo.playing) {
                            this._changeVideoPlayStatus(this.playingVideo);
                        }
                        this.playingVideo = null;
                    }
                },
                // change video play status
                _changeVideoPlayStatus(video) {
                    const videoElement = document.getElementById('preview-video-item-' + video
                        .scriptId);
                    video.playing = !video.playing;

                    if (video.playing) {
                        videoElement.play();
                    } else {
                        videoElement.pause();
                        videoElement.currentTime = 0;
                    }
                },
                // submit generate preview video
                async generatePreviewVideos() {
                    this.setFetchingStatus(true);
                    try {
                        const cloneConfigData = JSON.parse(JSON.stringify(Alpine.store(
                                'createVideoData')
                            .videoConfigData));
                        cloneConfigData.preview = true;

                        const res = await fetch(
                            '{{ route('topview.avatar-marketing-video.submit-task') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json'
                                },
                                body: JSON.stringify(cloneConfigData)
                            })

                        const resData = await res.json();

                        if (!res.ok || resData.status == 'error') {
                            throw new Error(resData.message ||
                                '{{ __('Something went wrong. Please contact support for assistance') }}'
                            );
                        }

                        this.taskId = resData.resData.taskId;
                        this.getPreviewVideoResult(resData.resData.taskId);
                    } catch (error) {
                        toastr.error(error?.message || error);
                        this.setFetchingStatus(false);
                    }
                },
                // fetch preview video result
                async getPreviewVideoResult(id) {
                    const ms = (time) => new Promise((resolve) => setTimeout(resolve, time));

                    while (true) {
                        try {
                            const res = await fetch(
                                '{{ route('topview.avatar-marketing-video.query-task') }}' +
                                `?taskId=${id}`, {
                                    method: 'get',
                                    headers: {
                                        'Accept': 'application/json'
                                    }
                                })

                            const resData = await res.json();

                            if (!res.ok || resData.status == 'error') {
                                throw new Error(resData.message ||
                                    '{{ __('Something went wrong. Please contact support for assistance') }}'
                                );
                            }

                            const previewResult = resData.resData;
                            if (previewResult.status == 'success') {
                                if (previewResult.previewVideos?.length > 0) {
                                    this.previews = previewResult.previewVideos;
                                    this.setFetchingStatus(false);
                                } else {
                                    throw new Error(previewResult.errorMsg ||
                                        '{{ __('Unexpected issue happen, Could you try again with other link?') }}'
                                    );
                                }
                                break;
                            } else if (previewResult.errorMsg == null || previewResult.errorMsg ==
                                '') {
                                await ms(2000);
                            } else {
                                throw new Error(previewResult.errorMsg ||
                                    '{{ __('Unexpected issue happen while generate the preview videos') }}'
                                );
                            }
                        } catch (error) {
                            toastr.error(error?.message || error);
                            console.error(error);
                            this.setFetchingStatus(false);
                            break;
                        }
                    }
                },
                // render final video
                async renderVideo() {
                    if (this.scriptId === '') {
                        toastr.error(
                            '{{ __('Please select preview video to render final video') }}');
                        return;
                    }

                    this.setFetchingStatus(true);

                    try {
                        const res = await fetch(
                            "{{ route('topview.avatar-marketing-video.export') }}", {
                                method: 'post',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json'
                                },
                                body: JSON.stringify({
                                    taskId: this.taskId,
                                    scriptId: this.scriptId
                                })
                            });

                        const resData = await res.json();

                        if (!res.ok || resData.status == 'error') {
                            throw new Error(resData.message ||
                                '{{ __('Something went wrong. Please contact support for assistance') }}'
                            );
                        }

                        const video = {
                            'task_id': resData.resData.taskId + ',' + this.scriptId,
                            'status': 'in_progress',
                            'used_ai_tool': 'topview'
                        };

                        window.location = "{{ route('dashboard.user.ai-influencer.index') }}";
                        // Alpine.store('exportedVideoData').addNewInProgressVideo(
                        //     video);
                        // Alpine.store('aiUrlToVideoData').toggleUrlToVideoWindow(
                        //     false);

                        // this.pausePlayingVideo()
                        // this.setFetchingStatus(false);
                    } catch (error) {
                        toastr.error(error.message || error);
                        console.error(error.message || error);
                        this.setFetchingStatus(false);
                    }
                },
                // set fetching status
                setFetchingStatus(status = true) {
                    this.fetching = status;
                    Alpine.store('appLoadingIndicator')[status ? 'show' : 'hide']();
                },
                // select preview video
                selectPreview(scriptId) {
                    if (this.scriptId == scriptId) return;
                    this.scriptId = scriptId;
                },
            }));
        });
    </script>
@endpush
